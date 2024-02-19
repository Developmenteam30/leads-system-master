<?php

namespace App\Http\Controllers\Api\Reports;

use App\Datasets\PayrollReportDataset;
use App\Helpers\DataTableFields;
use App\Jobs\CalculateEmployeeHoursJob;
use App\Jobs\GeneratePayrollReport;
use App\Jobs\SummarizeCallDetailLog;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DialerAgent;
use App\Models\DialerAgentPerformance;
use App\Responses\ErrorResponse;
use App\Rules\NumericEmptyOrWithDecimal;
use App\Validators\ApiJsonValidator;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PayrollController extends BaseController
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
        $view = Company::DIALER_REPORT_TYPE_PAYABLE;

        if ($startDate->diffInDays($endDate) < 0 || $startDate->diffInDays($endDate) > 6 || $endDate->isBefore($startDate)) {
            return ErrorResponse::json('Invalid date range specified', 400);
        }

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'product_ids' => $request->input('product_ids', []),
            'agent_type_ids' => $request->input('agent_type_ids', []),
            'company_ids' => $request->filled('company_ids') ? $request->input('company_ids') : DialerAgent::PAYROLL_COMPANY_IDS,
            'view' => $view,
            'search' => $request->input('search'),
        ];

        AuditLog::createFromRequest($request, 'REPORT:PAYROLL', array_merge($filters, [
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
            'payroll_amount',
            'bonus_amount',
            'total_amount',
            'agent',
            'internal_campaign_name',
            'internal_campaign_id',
        ], $agents->sum('holiday_amount') > 0 ? [
            'holiday_hours',
            'holiday_amount',
            'holiday_rate',
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
                ['label' => 'Payroll Amt', 'field' => 'payroll_amount', 'displayFormat' => 'currency'],
                ['label' => 'Bonus Amt', 'field' => 'bonus_amount', 'displayFormat' => 'currency'],
                ['label' => 'Holiday Hours', 'field' => 'holiday_hours', 'displayFormat' => 'number'],
                ['label' => 'Holiday Rate', 'field' => 'holiday_rate', 'displayFormat' => 'currency'],
                ['label' => 'Holiday Amt', 'field' => 'holiday_amount', 'displayFormat' => 'currency'],
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

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Payroll Report {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }

    /**
     * Update an agent's hours
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $agentId)
    {
        $agent = DialerAgent::find($agentId);
        if (!$agent) {
            return ErrorResponse::json('Agent not found', 400);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
            'internal_campaign_id' => 'required|bail|exists:dialer_products,id',
            'mon_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'tue_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'wed_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'thu_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'fri_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'sat_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'sun_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'mon_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'tue_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'wed_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'thu_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'fri_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'sat_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'sun_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'mon_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'tue_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'wed_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'thu_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'fri_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'sat_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'sun_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $view = $request->input('view', Company::DIALER_REPORT_TYPE_BILLABLE);
        $startDate = Carbon::parse($request->input('start_date'))->startOfWeek();
        $endDate = Carbon::parse($request->input('end_date'))->endOfWeek();
        $endDate->setTime(23, 59, 59); // To account for the last day

        if ($startDate->diffInDays($endDate) < 0 || $startDate->diffInDays($endDate) > 6 || $endDate->isBefore($startDate)) {
            return ErrorResponse::json('Invalid date range specified', 400);
        }

        DB::transaction(function () use ($agent, $request, $startDate, $endDate, $view) {

            $dates = new Collection(new DatePeriod(Carbon::parse($startDate), new DateInterval('P1D'), Carbon::parse($endDate)));
            $dates->each(function ($date) use ($agent, $request, $view) {
                $dayOfWeek = strtolower($date->format('D'));
                if ($request->has("{$dayOfWeek}_editable_hours") && false === $request->boolean("{$dayOfWeek}_disabled")) {

                    $record = DialerAgentPerformance::firstOrCreate(
                        [
                            'agent_id' => $agent->id,
                            'file_date' => $date->format('Y-m-d'),
                            'internal_campaign_id' => $request->input('internal_campaign_id'),
                        ]
                    );

                    $record->billable_time_override = $request->input("{$dayOfWeek}_editable_hours") * 60;
                    $record->coaching_time = $request->input("{$dayOfWeek}_coaching_minutes") * 60;
                    if (Company::DIALER_REPORT_TYPE_PAYABLE === $view) {
                        $record->huddle_time = $request->input("{$dayOfWeek}_huddle_minutes") * 60;
                    }
                    if ($record->isDirty()) {
                        AuditLog::createFromRequest($request, 'DIALER-AGENT-PERFORMANCES:SAVE', [
                            'oldValues' => $record->getOriginal(),
                            'newValues' => $record->toArray(),
                        ]);
                    }

                    $record->save();
                }
            });
        });

        return response([]);
    }

    /**
     * Bulk update payroll amounts
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function bulk_update(Request $request)
    {
        ApiJsonValidator::validate($request->all(), [
            'row_ids' => 'required|bail|array|min:1',
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
            'mon_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'tue_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'wed_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'thu_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'fri_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'sat_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'sun_editable_hours' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'mon_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'tue_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'wed_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'thu_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'fri_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'sat_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'sun_coaching_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'mon_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'tue_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'wed_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'thu_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'fri_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'sat_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
            'sun_huddle_minutes' => ['bail', 'nullable', 'numeric', 'integer', 'between:0,1440'],
        ]);

        $startDate = Carbon::parse($request->input('start_date'))->startOfWeek();
        $endDate = Carbon::parse($request->input('end_date'))->endOfWeek();
        $endDate->setTime(23, 59, 59); // To account for the last day

        if ($startDate->diffInDays($endDate) < 0 || $startDate->diffInDays($endDate) > 6 || $endDate->isBefore($startDate)) {
            return ErrorResponse::json('Invalid date range specified', 400);
        }
        $rowIds = new Collection($request->input('row_ids'));

        DB::transaction(function () use ($request, $startDate, $endDate, $rowIds) {

            $dates = new Collection(new DatePeriod(Carbon::parse($startDate), new DateInterval('P1D'), Carbon::parse($endDate)));
            $dates->each(function ($date) use ($request, $rowIds) {
                $dayOfWeek = strtolower($date->format('D'));
                if ($request->filled("{$dayOfWeek}_edit_type")) {

                    $rowIds->each(function ($rowId) use ($request, $date, $dayOfWeek) {
                        list($agentId, $campaignId) = explode('-', $rowId);
                        $agent = DialerAgent::find($agentId);
                        if (!$agent) {
                            return ErrorResponse::json('Agent not found', 400);
                        }

                        $record = DialerAgentPerformance::firstOrCreate(
                            [
                                'agent_id' => $agent->id,
                                'file_date' => $date->format('Y-m-d'),
                                'internal_campaign_id' => $campaignId,
                            ]
                        );

                        $property = "{$dayOfWeek}_editable_hours";
                        if (null !== $request->input($property) && '' !== $request->input($property)) {
                            if ('set' === $request->input("{$dayOfWeek}_edit_type")) {
                                $record->billable_time_override = $request->input("{$property}") * 60;
                            } else {
                                $record->billable_time_override += $request->input("{$property}") * 60;
                            }
                        }

                        $property = "{$dayOfWeek}_coaching_minutes";
                        if (null !== $request->input($property) && '' !== $request->input($property)) {
                            if ('set' === $request->input("{$dayOfWeek}_edit_type")) {
                                $record->coaching_time = $request->input("{$property}") * 60;
                            } else {
                                $record->coaching_time += $request->input("{$property}") * 60;
                            }
                        }

                        $property = "{$dayOfWeek}_huddle_minutes";
                        if (null !== $request->input($property) && '' !== $request->input($property)) {
                            if ('set' === $request->input("{$dayOfWeek}_edit_type")) {
                                $record->huddle_time = $request->input("{$property}") * 60;
                            } else {
                                $record->huddle_time += $request->input("{$property}") * 60;
                            }
                        }

                        if ($record->isDirty()) {
                            AuditLog::createFromRequest($request, 'DIALER-AGENT-PERFORMANCES:SAVE', [
                                'oldValues' => $record->getOriginal(),
                                'newValues' => $record->toArray(),
                            ]);
                        }

                        $record->save();
                    });
                }
            });
        });

        return response([]);
    }

    /**
     * Send the payroll report email
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
            'include_qa' => 'nullable|string|in:include,exclude',
            'product_id' => 'nullable|numeric|exists:dialer_products,id',
            'agent_type_ids' => 'nullable|string',
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

        $log = AuditLog::createFromRequest($request, 'PAYROLL:EMAIL-REPORT', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        GeneratePayrollReport::dispatch([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'view' => Company::DIALER_REPORT_TYPE_PAYABLE,
            'product_id' => $request->input('product_id'),
            'agent_type_ids' => $request->input('agent_type_ids', []),
            'company_ids' => DialerAgent::PAYROLL_COMPANY_IDS,
        ], $request->user(), $log->logId);

        return response([]);
    }


    /**
     * Send the payroll report email
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function recalculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $endDate->setTime(23, 59, 59); // To account for the last day

        $dates = new Collection(new DatePeriod(Carbon::parse($startDate), new DateInterval('P1D'), Carbon::parse($endDate)));
        $dates->each(function ($date) {
            CalculateEmployeeHoursJob::dispatch($date->format('Y-m-d'), null);
            SummarizeCallDetailLog::dispatch($date->format('Y-m-d'), null);
        });

        return response([]);
    }
}
