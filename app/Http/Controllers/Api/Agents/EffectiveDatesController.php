<?php

namespace App\Http\Controllers\Api\Agents;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAccessArea;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerCampaignDefault;
use App\Responses\ErrorResponse;
use App\Rules\NumericEmptyOrWithDecimal;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EffectiveDatesController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agentId)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'agentId' => 'required|bail|exists:dialer_agents,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $rows = DialerAgentEffectiveDate::query()
            ->with([
                'agentType',
                'paymentType',
                'product',
            ])
            ->where('agent_id', $agentId)
            ->orderBy('start_date')
            ->get();

        $rows->map(function ($row) {
            $row->is_training = (bool) $row->is_training;
            $row->is_training_display = $row->is_training;
            $row->agent_type_name = $row->agentType->name ?? '';
            $row->payment_type_name = $row->paymentType->name ?? '';
            $row->product_name = $row->product->name ?? '';
            $row->payable_rate_formatted = !empty($row->payable_rate) ? '$'.number_format($row->payable_rate, 2) : '';
            $row->billable_rate_formatted = !empty($row->billable_rate) ? '$'.number_format($row->billable_rate, 2) : '';
            $row->bonus_rate_formatted = !empty($row->bonus_rate) ? '$'.number_format($row->bonus_rate, 2) : '';

            return $row;
        });

        $allow_list = array_merge([
            'id',
            'agent_id',
            'agent_type_id',
            'agent_type_name',
            'payment_type_id',
            'payment_type_name',
            'product_id',
            'product_name',
            'payable_rate',
            'payable_rate_formatted',
            'bonus_rate',
            'bonus_rate_formatted',
            'start_date',
            'is_training',
            'is_training_display',
            'end_date',
            'termination_reason_id',
        ],
            $request->user()->hasAccessToArea("ACCESS_AREA_BILLABLE_RATES") ?
                [
                    'billable_rate',
                    'billable_rate_formatted',
                ] : [],
        );

        $datatable = [
            'columns' => [
                ['label' => 'Entry ID', 'field' => 'id'],
                ['label' => 'Agent Type', 'field' => 'agent_type_name'],
                ['label' => 'Payment Type', 'field' => 'payment_type_name'],
                ['label' => 'Campaign', 'field' => 'product_name'],
                ['label' => 'Billable Rate', 'field' => 'billable_rate_formatted'],
                ['label' => 'Payable Rate', 'field' => 'payable_rate_formatted'],
                ['label' => 'Bonus Rate', 'field' => 'bonus_rate_formatted'],
                ['label' => 'Start Date', 'field' => 'start_date'],
                ['label' => 'Training', 'field' => 'is_training_display', 'displayFormat' => DataTableFields::BOOLEAN_ICON_YES,],
                ['label' => 'End Date', 'field' => 'end_date'],
            ],
            'rows' => $rows,
        ];

        return DataTableFields::getByAllowList($datatable, $allow_list);
    }

    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function openEnded(Request $request, $agentId)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'agentId' => 'required|bail|exists:dialer_agents,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        return [
            'value' => !empty(DialerAgentEffectiveDate::query()
                ->where('agent_id', $agentId)
                ->whereNull('end_date')
                ->first()),
        ];
    }

    /**
     * Add or update an agent effective date entry
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $effectiveDateId = null)
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'required|bail|exists:dialer_agents,id',
            'agent_type_id' => 'bail|required|exists:dialer_agent_types,id',
            'payment_type_id' => 'bail|required|exists:dialer_payment_types,id',
            'product_id' => 'bail|required|exists:dialer_products,id',
            'billable_rate' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'payable_rate' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'bonus_rate' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'start_date' => 'bail|required|date',
            'is_training' => 'bail|nullable|boolean',
            'end_date' => 'bail|nullable|date|after_or_equal:start_date',
            'termination_reason_id' => 'bail|required_with:end_date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        if (!empty($effectiveDateId)) {
            $effectiveDate = DialerAgentEffectiveDate::find($effectiveDateId);
            if (!$effectiveDate) {
                return ErrorResponse::json('Effective date entry not found', 400);
            }
        } else {
            $effectiveDate = new DialerAgentEffectiveDate();
            $effectiveDate->agent_id = $request->input('agent_id');
        }

        if (empty($request->input('end_date'))) {
            $effectiveDateCheck = DialerAgentEffectiveDate::query()
                ->where('id', '<>', $effectiveDate->id)
                ->where('agent_id', $effectiveDate->agent_id)
                ->where(function ($query) use ($request) {
                    $query->whereNull('end_date')
                        ->orWhere('start_date', '>', $request->input('start_date'));
                })
                ->first();

            if ($effectiveDateCheck) {
                return ErrorResponse::json("There is already an entry with an empty end date on file: {$effectiveDateCheck->start_date}.  There can only be one entry with an empty end date and it must be the last in the series.",
                    400);
            }
        }

        if (!empty($request->input('start_date'))) {
            $effectiveDateCheck = DialerAgentEffectiveDate::query()
                ->where('id', '<>', $effectiveDate->id)
                ->where('agent_id', $effectiveDate->agent_id)
                ->where(function ($query) use ($request) {
                    $query->where('start_date', '<=', $request->input('start_date'))
                        ->orWhereNull('start_date');
                })
                ->where(function ($query) use ($request) {
                    $query->where('end_date', '>=', $request->input('start_date'))
                        ->orWhereNull('end_date');
                })
                ->first();

            if ($effectiveDateCheck) {
                if (!empty($effectiveDateCheck->end_date)) {
                    return ErrorResponse::json("The start date overlaps an existing date range: {$effectiveDateCheck->start_date} to {$effectiveDateCheck->end_date}.", 400);
                } else {
                    return ErrorResponse::json("The start date overlaps an existing date range with no end date: {$effectiveDateCheck->start_date}.  Please set a end date on this existing entry first.",
                        400);
                }
            }
        }

        if (!empty($request->input('end_date'))) {
            $effectiveDateCheck = DialerAgentEffectiveDate::query()
                ->where('id', '<>', $effectiveDate->id)
                ->where('agent_id', $effectiveDate->agent_id)
                ->where(function ($query) use ($request) {
                    $query->where('start_date', '<=', $request->input('end_date'))
                        ->orWhereNull('start_date');
                })
                ->where(function ($query) use ($request) {
                    $query->where('end_date', '>=', $request->input('end_date'))
                        ->orWhereNull('end_date');
                })
                ->first();

            if ($effectiveDateCheck) {
                if (!empty($effectiveDateCheck->start_date)) {
                    return ErrorResponse::json("The end date overlaps an existing date range: {$effectiveDateCheck->start_date} to {$effectiveDateCheck->end_date}.", 400);
                } else {
                    return ErrorResponse::json("The end date overlaps an existing date range with no start date: {$effectiveDateCheck->start_date}.  Please set a start date on this existing entry first.",
                        400);
                }
            }
        }

        DB::transaction(function () use ($effectiveDate, $request) {
            $effectiveDate->agent_type_id = intval($request->input('agent_type_id'));
            $effectiveDate->payment_type_id = $request->input('payment_type_id');
            $effectiveDate->product_id = $request->input('product_id');
            if ($request->user()->hasAccessToArea("ACCESS_AREA_BILLABLE_RATES")) {
                $effectiveDate->billable_rate = strlen($request->input('billable_rate')) > 0 ? $request->input('billable_rate') : null;
            } elseif (!$effectiveDate->exists) {
                // Set a default value if it's a new record and the user does not have access to the field.
                $default = DialerCampaignDefault::query()
                    ->where('company_id', $effectiveDate->agent->company_id ?? null)
                    ->where('campaign_id', $effectiveDate->product_id ?? null)
                    ->first();

                $effectiveDate->billable_rate = $default->billable_rate ?? null;
            }
            $effectiveDate->payable_rate = strlen($request->input('payable_rate')) > 0 ? $request->input('payable_rate') : null;
            $effectiveDate->bonus_rate = strlen($request->input('bonus_rate')) > 0 ? $request->input('bonus_rate') : null;
            $effectiveDate->start_date = !empty($request->input('start_date')) ? $request->input('start_date') : null;
            $effectiveDate->is_training = !empty($request->input('is_training')) ? 1 : 0;
            $effectiveDate->end_date = !empty($request->input('end_date')) ? $request->input('end_date') : null;
            $effectiveDate->termination_reason_id = !empty($request->input('end_date')) ? $request->input('termination_reason_id') : null;
            $effectiveDate->save();
        });

        return response([]);
    }
}
