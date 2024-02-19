<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAccessArea;
use App\Models\DialerAgent;
use App\Models\DialerAgentType;
use App\Models\User;
use App\Responses\ErrorResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class LicensingController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'bail|string|nullable',
            'include_inactive' => 'bail|string',
            'product_id' => 'nullable|string|exists:dialer_products,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        AuditLog::createFromRequest($request, 'REPORT:LICENSING', [
            'search' => $request->input('search'),
            'include_inactive' => $request->input('include_inactive'),
            'product_id' => $request->input('product_id'),
        ]);

        $agents = DialerAgent::query()
            ->with([
                'paymentType',
                'product',
                'latestActiveEffectiveDate',
            ])
            ->select([
                'dialer_agents.id',
                'dialer_agents.agent_name',
            ])
            ->when(!$request->boolean('include_inactive'), function ($query) {
                $query->whereHas('latestActiveEffectiveDate', function (Builder $query) {
                    $query->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', now(config('settings.timezone.local')));
                });
            })
            ->when($request->filled('product_id'), function ($query) use ($request) {
                $query->whereHas('latestActiveEffectiveDate', function (Builder $query) use ($request) {
                    $query->where('product_id', $request->input('product_id'));
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('agent_name', 'LIKE', '%'.$request->input('search').'%');
            })
            ->whereHas('latestActiveEffectiveDate', function (Builder $query) {
                $query->where('agent_type_id', DialerAgentType::AGENT);
            })
            ->get();

        $agents = $agents->map(function ($agent) {
            $agent->is_active = !empty($agent->latestActiveEffectiveDate) && (empty($agent->latestActiveEffectiveDate->end_date) || Carbon::parse($agent->latestActiveEffectiveDate->end_date)->gte(now()));
            $agent->product_name = $agent->getEffectiveValuesForDate()->product->name ?? '';
            $agent->payment_type_name = $agent->getEffectiveValuesForDate()->paymentType->name ?? '';
            $agent->billable_rate_formatted = !empty($agent->getEffectiveValuesForDate()->billable_rate) ? '$'.number_format($agent->getEffectiveValuesForDate()->billable_rate, 2) : '';

            return $agent;
        })->sortBy([
            ['is_active', 'desc'],
            ['agent_name', 'asc'],
        ])->values();

        $allow_list = array_merge([
            'agent_name',
            'product_name',
            'payment_type_name',
            'is_active',
            'is_active_icon',
        ],
            $request->user()->hasAccessToArea("ACCESS_AREA_BILLABLE_RATES") ?
                [
                    'billable_rate',
                    'billable_rate_formatted',
                ] : [],
        );

        $datatable = [
            'columns' => [
                ['label' => 'Agent Name', 'field' => 'agent_name'],
                ['label' => 'Campaign', 'field' => 'product_name'],
                ['label' => 'Payment Type', 'field' => 'payment_type_name'],
                ['label' => 'Billable Rate', 'field' => 'billable_rate_formatted'],
                ['label' => 'Active', 'field' => 'is_active', 'displayFormat' => 'boolean_icon'],
            ],
            'rows' => $agents,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Licensing Report.xlsx");
    }
}
