<?php

namespace App\Http\Controllers\Api\Reports;

use App\Datasets\PayrollReportDataset;
use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\Company;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class ClientHoursController extends BaseController
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
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $endDate->setTime(23, 59, 59); // To account for the last day

        $view = Company::DIALER_REPORT_TYPE_BILLABLE;

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'view' => $view,
            'search' => $request->input('search'),
        ];

        AuditLog::createFromRequest($request, 'REPORT:CLIENT-HOURS', array_merge($filters, [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));

        $agents = PayrollReportDataset::getWeeklyValues($filters);

        $allow_list = [
            'agent_id',
            'agent_name_role',
            'mon',
            'tue',
            'wed',
            'thu',
            'fri',
            'sat',
            'sun',
            'training_hours',
            'regular_hours',
            'qa_hours',
            'overtime_hours',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'Agent Name', 'field' => 'agent_name_role', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Mon Hours', 'field' => 'mon', 'displayFormat' => 'number'],
                ['label' => 'Tue Hours', 'field' => 'tue', 'displayFormat' => 'number'],
                ['label' => 'Wed Hours', 'field' => 'wed', 'displayFormat' => 'number'],
                ['label' => 'Thu Hours', 'field' => 'thu', 'displayFormat' => 'number'],
                ['label' => 'Fri Hours', 'field' => 'fri', 'displayFormat' => 'number'],
                ['label' => 'Sat Hours', 'field' => 'sat', 'displayFormat' => 'number'],
                ['label' => 'Sun Hours', 'field' => 'sun', 'displayFormat' => 'number'],
                ['label' => 'Training Hours', 'field' => 'training_hours', 'displayFormat' => 'number'],
                ['label' => 'Regular Hours', 'field' => 'regular_hours', 'displayFormat' => 'number'],
                ['label' => 'OT Hours', 'field' => 'overtime_hours', 'displayFormat' => 'number'],
                ['label' => 'QA Hours', 'field' => 'qa_hours', 'displayFormat' => 'number'],
            ],
            'rows' => $agents,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Client Hours Report {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }
}
