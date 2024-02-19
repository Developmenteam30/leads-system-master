<?php

namespace App\Http\Controllers\Api\Reports;

use App\Datasets\PayrollReportDataset;
use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DialerAccessArea;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class CallCenterHoursController extends BaseController
{
    /**
     * Load a report
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
            'search' => 'nullable|string',
            'company_ids' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $endDate->setTime(23, 59, 59); // To account for the last day

        if (!$request->user()->hasAccessToArea("ACCESS_AREA_UNRESTRICTED_CALL_CENTER_REPORTS")) {
            if (empty($request->user()->company_id)) {
                return ErrorResponse::json('No company ID is set.', 401);
            } else {
                $company = Company::find($request->user()->company_id);
                if (!$company) {
                    return ErrorResponse::json('Company is not found.', 401);
                }
                $request->merge(['company_ids' => $request->user()->company_id]);
            }
        }

        $view = Company::DIALER_REPORT_TYPE_PAYABLE;

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'view' => $view,
            'search' => $request->input('search'),
            'company_ids' => $request->input('company_ids'),
        ];

        AuditLog::createFromRequest($request, 'REPORT:CALL-CENTER-HOURS', array_merge($filters, [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));

        $agents = PayrollReportDataset::getWeeklyValues($filters);

        $allow_list = [
            'agent_id',
            'agent_name',
            'mon',
            'tue',
            'wed',
            'thu',
            'fri',
            'sat',
            'sun',
            'raw_hours',
            'raw_rate',
            'raw_amount',
            'payroll_amount',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'Agent Name', 'field' => 'agent_name', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Mon Hours', 'field' => 'mon', 'displayFormat' => 'number'],
                ['label' => 'Tue Hours', 'field' => 'tue', 'displayFormat' => 'number'],
                ['label' => 'Wed Hours', 'field' => 'wed', 'displayFormat' => 'number'],
                ['label' => 'Thu Hours', 'field' => 'thu', 'displayFormat' => 'number'],
                ['label' => 'Fri Hours', 'field' => 'fri', 'displayFormat' => 'number'],
                ['label' => 'Sat Hours', 'field' => 'sat', 'displayFormat' => 'number'],
                ['label' => 'Sun Hours', 'field' => 'sun', 'displayFormat' => 'number'],
                ['label' => 'Total Hours', 'field' => 'raw_hours', 'displayFormat' => 'number'],
                ['label' => ucfirst($view).' Rate', 'field' => 'raw_rate', 'displayFormat' => 'currency'],
                ['label' => ucfirst($view).' Amt', 'field' => 'raw_amount', 'displayFormat' => 'currency'],
            ],
            'rows' => $agents,
            'totals' => [
                [
                    'agent_name' => 'TOTALS',
                    'mon' => round($agents->sum('mon'), 2),
                    'tue' => round($agents->sum('tue'), 2),
                    'wed' => round($agents->sum('wed'), 2),
                    'thu' => round($agents->sum('thu'), 2),
                    'fri' => round($agents->sum('fri'), 2),
                    'sat' => round($agents->sum('sat'), 2),
                    'sun' => round($agents->sum('sun'), 2),
                    'raw_hours' => round($agents->sum('raw_hours'), 2),
                    'raw_rate' => round($agents->where('raw_rate', '>', 0)->average('raw_rate'), 2),
                    'raw_amount' => round($agents->sum('raw_amount'), 2),
                ],
            ],
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Call Center Hours Report {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }
}
