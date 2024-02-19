<?php

namespace App\Http\Controllers\Api\Reports;

use App\Datasets\PayrollReportDataset;
use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DialerAgent;
use App\Models\DialerAgentType;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class AgentDailyStatsController extends BaseController
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

        $view = Company::DIALER_REPORT_TYPE_PAYABLE;

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'company_ids' => DialerAgent::PAYROLL_COMPANY_IDS,
            'agent_type_ids' => [DialerAgentType::AGENT],
            'view' => $view,
            'search' => $request->input('search'),
        ];

        AuditLog::createFromRequest($request, 'REPORT:AGENT-DAILY-STATS', array_merge($filters, [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));

        $agents = PayrollReportDataset::getWeeklyValues($filters);

        $allow_list = array_merge([
            'agent_id',
            'agent_name',
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
            'mon_successful_transfers',
            'tue_successful_transfers',
            'wed_successful_transfers',
            'thu_successful_transfers',
            'fri_successful_transfers',
            'sat_successful_transfers',
            'sun_successful_transfers',
            'mon_failed_transfers',
            'tue_failed_transfers',
            'wed_failed_transfers',
            'thu_failed_transfers',
            'fri_failed_transfers',
            'sat_failed_transfers',
            'sun_failed_transfers',
            'mon_billable_transfers',
            'tue_billable_transfers',
            'wed_billable_transfers',
            'thu_billable_transfers',
            'fri_billable_transfers',
            'sat_billable_transfers',
            'sun_billable_transfers',
            'transfers',
            'failed_transfers',
            'billable_transfers',
        ], $agents->sum('holiday_amount') > 0 ? [
            'holiday_hours',
        ] : [],
        );

        $datatable = [
            'columns' => [
                ['label' => 'Agent Name', 'field' => 'agent_name', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Mon Hours', 'field' => 'mon', 'displayFormat' => 'number'],
                ['label' => 'Mon ST', 'field' => 'mon_successful_transfers'],
                ['label' => 'Mon FT', 'field' => 'mon_failed_transfers'],
                ['label' => 'Mon BT', 'field' => 'mon_billable_transfers'],
                ['label' => 'Tue Hours', 'field' => 'tue', 'displayFormat' => 'number'],
                ['label' => 'Tue ST', 'field' => 'tue_successful_transfers'],
                ['label' => 'Tue FT', 'field' => 'tue_failed_transfers'],
                ['label' => 'Tue BT', 'field' => 'tue_billable_transfers'],
                ['label' => 'Wed Hours', 'field' => 'wed', 'displayFormat' => 'number'],
                ['label' => 'Wed ST', 'field' => 'wed_successful_transfers'],
                ['label' => 'Wed FT', 'field' => 'wed_failed_transfers'],
                ['label' => 'Wed BT', 'field' => 'wed_billable_transfers'],
                ['label' => 'Thu Hours', 'field' => 'thu', 'displayFormat' => 'number'],
                ['label' => 'Thu ST', 'field' => 'thu_successful_transfers'],
                ['label' => 'Thu FT', 'field' => 'thu_failed_transfers'],
                ['label' => 'Thu BT', 'field' => 'thu_billable_transfers'],
                ['label' => 'Fri Hours', 'field' => 'fri', 'displayFormat' => 'number'],
                ['label' => 'Fri ST', 'field' => 'fri_successful_transfers'],
                ['label' => 'Fri FT', 'field' => 'fri_failed_transfers'],
                ['label' => 'Fri BT', 'field' => 'fri_billable_transfers'],
                ['label' => 'Sat Hours', 'field' => 'sat', 'displayFormat' => 'number'],
                ['label' => 'Sat ST', 'field' => 'sat_successful_transfers'],
                ['label' => 'Sat FT', 'field' => 'sat_failed_transfers'],
                ['label' => 'Sat BT', 'field' => 'sat_billable_transfers'],
                ['label' => 'Sun Hours', 'field' => 'sun', 'displayFormat' => 'number'],
                ['label' => 'Sun ST', 'field' => 'sun_successful_transfers'],
                ['label' => 'Sun FT', 'field' => 'sun_failed_transfers'],
                ['label' => 'Sun BT', 'field' => 'sun_billable_transfers'],
                ['label' => 'Training Hours', 'field' => 'training_hours', 'displayFormat' => 'number'],
                ['label' => 'Regular Hours', 'field' => 'regular_hours', 'displayFormat' => 'number'],
                ['label' => 'OT Hours', 'field' => 'overtime_hours', 'displayFormat' => 'number'],
                ['label' => 'Holiday Hours', 'field' => 'holiday_hours', 'displayFormat' => 'number'],
                ['label' => 'ST Total', 'field' => 'transfers'],
                ['label' => 'FT Total', 'field' => 'failed_transfers'],
                ['label' => 'BT Total', 'field' => 'billable_transfers'],
            ],
            'rows' => $agents,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Agent Daily Stats Report {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }
}
