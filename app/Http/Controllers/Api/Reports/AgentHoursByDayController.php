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

class AgentHoursByDayController extends BaseController
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
            'product_ids' => 'nullable|string',
            'view' => 'nullable|string|in:billable,payable',
            'company_ids' => 'nullable|string',
            'agent_type_ids' => 'nullable|string',
            'search' => 'bail|string|nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $endDate->setTime(23, 59, 59); // To account for the last day

        if ($startDate->diffInDays($endDate) < 0 || $startDate->diffInDays($endDate) > 6 || $endDate->isBefore($startDate)) {
            return ErrorResponse::json('Invalid date range specified', 400);
        }

        if (!$request->user()->hasAccessToArea("ACCESS_AREA_BILLABLE_RATES")) {
            $request->merge(['view' => Company::DIALER_REPORT_TYPE_PAYABLE]);
        }

        if (!$request->user()->hasAccessToArea("ACCESS_AREA_ADD_EDIT_EMPLOYEES")) {
            $request->merge(['company_ids' => DialerAgent::PAYROLL_COMPANY_IDS]);
            $request->merge(['agent_type_ids' => [DialerAgentType::AGENT]]);
        }

        $view = $request->input('view', Company::DIALER_REPORT_TYPE_PAYABLE);

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'view' => $view,
            'company_ids' => $request->input('company_ids'),
            'product_ids' => $request->input('product_ids', []),
            'agent_type_ids' => $request->input('agent_type_ids', []),
            'search' => $request->input('search'),
        ];

        AuditLog::createFromRequest($request, 'REPORT:AGENT-HOURS-BY-DAY', array_merge($filters, [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));

        $agents = PayrollReportDataset::getWeeklyValues($filters);

        $allow_list = array_merge([
            'agent_id',
            'agent_name',
            'file_date',
            'training_hours',
            'training_rate',
            'training_amount',
            'regular_hours',
            'regular_amount',
            'regular_rate',
            'qa_hours',
            'qa_rate',
            'qa_amount',
            'overtime_hours',
            'overtime_rate',
            'overtime_amount',
            'total_amount',
            'agent',
            'internal_campaign_name',
            'internal_campaign_id',
        ], $agents->sum('holiday_amount') > 0 ? [
            'holiday_hours',
            'holiday_amount',
            'holiday_rate',
        ] : [],
            Company::DIALER_REPORT_TYPE_PAYABLE === $request->input('view') ? [
                'payroll_amount',
                'bonus_amount',
            ] : []);

        $weekdays = [
            'mon',
            'tue',
            'wed',
            'thu',
            'fri',
            'sat',
            'sun',
        ];

        foreach ($weekdays as $weekday) {
            $allow_list[] = $weekday;
            $allow_list[] = "{$weekday}_training";
            $allow_list[] = "{$weekday}_disabled";
            $allow_list[] = "{$weekday}_holiday";
            $allow_list[] = "{$weekday}_editable_hours";
            $allow_list[] = "{$weekday}_coaching_minutes";
            $allow_list[] = "{$weekday}_huddle_minutes";
            $allow_list[] = "{$weekday}_break_minutes";
            $allow_list[] = "{$weekday}_agent_average";
            $allow_list[] = "{$weekday}_company_average";
            $allow_list[] = "{$weekday}_bonus_level";
            $allow_list[] = "{$weekday}_transfers";
            $allow_list[] = "{$weekday}_billable_transfers";
            $allow_list[] = "{$weekday}_bonus_amount";
            $allow_list[] = "{$weekday}_effective_bonus_rate";
        }

        $datatable = [
            'columns' => [
                ['label' => 'Agent Name', 'field' => 'agent_name', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Campaign', 'field' => 'internal_campaign_name', 'displayFormat' => 'text',],
                ['label' => 'Mon Hours', 'field' => 'mon', 'displayFormat' => 'number'],
                ['label' => 'Tue Hours', 'field' => 'tue', 'displayFormat' => 'number'],
                ['label' => 'Wed Hours', 'field' => 'wed', 'displayFormat' => 'number'],
                ['label' => 'Thu Hours', 'field' => 'thu', 'displayFormat' => 'number'],
                ['label' => 'Fri Hours', 'field' => 'fri', 'displayFormat' => 'number'],
                ['label' => 'Sat Hours', 'field' => 'sat', 'displayFormat' => 'number'],
                ['label' => 'Sun Hours', 'field' => 'sun', 'displayFormat' => 'number'],
                ['label' => 'Training Hours', 'field' => 'training_hours', 'displayFormat' => 'number'],
                ['label' => 'Training Rate', 'field' => 'training_rate', 'displayFormat' => 'currency'],
                ['label' => 'Training Amt', 'field' => 'training_amount', 'displayFormat' => 'currency'],
                ['label' => 'Regular Hours', 'field' => 'regular_hours', 'displayFormat' => 'number'],
                ['label' => 'Regular Rate', 'field' => 'regular_rate', 'displayFormat' => 'currency'],
                ['label' => 'Regular Amt', 'field' => 'regular_amount', 'displayFormat' => 'currency'],
                ['label' => 'QA Hours', 'field' => 'qa_hours', 'displayFormat' => 'number'],
                ['label' => 'QA Rate', 'field' => 'qa_rate', 'displayFormat' => 'currency'],
                ['label' => 'QA Amt', 'field' => 'qa_amount', 'displayFormat' => 'currency'],
                ['label' => 'OT Hours', 'field' => 'overtime_hours', 'displayFormat' => 'number'],
                ['label' => 'OT Rate', 'field' => 'overtime_rate', 'displayFormat' => 'currency'],
                ['label' => 'OT Amt', 'field' => 'overtime_amount', 'displayFormat' => 'currency'],
                ['label' => 'Holiday Hours', 'field' => 'holiday_hours', 'displayFormat' => 'number'],
                ['label' => 'Holiday Rate', 'field' => 'holiday_rate', 'displayFormat' => 'currency'],
                ['label' => 'Holiday Amt', 'field' => 'holiday_amount', 'displayFormat' => 'currency'],
                ['label' => 'Payroll Amt', 'field' => 'payroll_amount', 'displayFormat' => 'currency'],
                ['label' => 'Bonus Amt', 'field' => 'bonus_amount', 'displayFormat' => 'currency'],
                ['label' => ucfirst($view).' Amt', 'field' => 'total_amount', 'displayFormat' => 'currency'],
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
                    'training_hours' => round($agents->sum('training_hours'), 2),
                    'training_rate' => round($agents->where('training_rate', '>', 0)->average('training_rate'), 2),
                    'training_amount' => round($agents->sum('training_amount'), 2),
                    'regular_hours' => round($agents->sum('regular_hours'), 2),
                    'regular_rate' => round($agents->where('regular_rate', '>', 0)->average('regular_rate'), 2),
                    'regular_amount' => round($agents->sum('regular_amount'), 2),
                    'qa_hours' => round($agents->sum('qa_hours'), 2),
                    'qa_rate' => round($agents->where('qa_rate', '>', 0)->average('qa_rate'), 2),
                    'qa_amount' => round($agents->sum('qa_amount'), 2),
                    'overtime_hours' => round($agents->sum('overtime_hours'), 2),
                    'overtime_rate' => round($agents->where('overtime_rate', '>', 0)->average('overtime_rate'), 2),
                    'overtime_amount' => round($agents->sum('overtime_amount'), 2),
                    'payroll_amount' => round($agents->sum('payroll_amount'), 2),
                    'bonus_amount' => round($agents->sum('bonus_amount'), 2),
                    'holiday_hours' => round($agents->sum('holiday_hours'), 2),
                    'holiday_rate' => round($agents->where('holiday_rate', '>', 0)->average('holiday_rate'), 2),
                    'holiday_amount' => round($agents->sum('holiday_amount'), 2),
                    'total_amount' => round($agents->sum('total_amount'), 2),
                ],
            ],
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Agent Hours by Day {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }

}
