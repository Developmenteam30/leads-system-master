<?php

namespace App\Http\Controllers\Api\Agents;

use App\Helpers\DataTableFields;
use App\Http\Controllers\Api\ForgotController;
use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerAgentPip;
use App\Models\DialerAgentType;
use App\Models\DialerCampaignDefault;
use App\Models\DialerPaymentType;
use App\Responses\ErrorResponse;
use App\Rules\NumericEmptyOrWithDecimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ManageController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  Request  $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'bail|string|nullable',
            'statuses' => 'bail|string|nullable',
            'title' => 'bail|string',
            'team_id' => 'bail|nullable|exists:dialer_teams,id',
            'company_ids' => 'bail|nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $agentTypeId = self::getAgentTypeId($request->input('title'));

        AuditLog::createFromRequest($request, 'DIALER-AGENT:LIST', [
            'search' => $request->input('search'),
            'statuses' => $request->input('statuses'),
            'title' => $request->input('title'),
            'company_ids' => $request->input('company_ids'),
            'product_id' => $request->input('product_id'),
            'team_id' => $request->input('team_id'),
        ]);

        $statuses = explode(',', $request->input('statuses', ''));

        $agents = DialerAgent::query()
            ->leftJoin('dialer_teams', 'dialer_teams.id', 'dialer_agents.team_id')
            ->with([
                'company' => function ($query) {
                    $query->select('idCompany', 'name');
                },
                'paymentType',
                'product',
                'companies',
                'effectiveDates',
                'latestActiveEffectiveDate' => function ($query) {
                    $query->with([
                        'product',
                        'paymentType',
                    ]);
                },
                'firstPerformanceDate',
            ])
            ->select([
                'dialer_agents.id',
                'dialer_agents.agent_name',
                'dialer_agents.company_id',
                'dialer_agents.email',
                'dialer_agents.team_id',
                'dialer_agents.date_of_birth',
                'dialer_agents.access_role_id',
                DB::raw('dialer_teams.name AS team_name'),
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('dialer_agents.agent_name', 'LIKE', '%'.$request->input('search').'%')
                    ->orWhere('dialer_agents.id', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->filled('company_ids'), function ($query) use ($request) {
                return $query->whereIn('dialer_agents.company_id', explode(',', $request->input('company_ids')));
            })
            ->when($request->filled('team_id'), function ($query) use ($request) {
                $query->where('dialer_agents.team_id', $request->input('team_id'));
            })
            ->orderBy('dialer_agents.agent_name')
            ->get()
            ->append([
                'companies_list',
                'effectiveHireDate',
                'effectiveTerminationDate',
                'effectiveTerminationReason',
                'mostRecentEffectiveDate',
            ]);

        $agents = $agents->filter(function ($agent) use ($request, $agentTypeId) {
            return empty($agent->mostRecentEffectiveDate) || $agentTypeId == $agent->mostRecentEffectiveDate->agent_type_id;
        })->values();

        $agents->map(function ($agent) {
            $agent->is_active = empty($agent->latestActiveEffectiveDate->end_date);
            $agent->product_name = $agent->latestActiveEffectiveDate->product->name ?? '';
            $agent->product_id = $agent->latestActiveEffectiveDate->product->id ?? null;
            $agent->payment_type_name = $agent->latestActiveEffectiveDate->paymentType->name ?? '';
            $agent->access_role_name = $agent->accessRole->name ?? '';
            $agent->company_name = $agent->company->name ?? '';
            $agent->payable_rate_formatted = $agent->latestActiveEffectiveDate->payable_rate ?? '';
            $agent->billable_rate_formatted = $agent->latestActiveEffectiveDate->billable_rate ?? '';
            $agent->bonus_rate_formatted = $agent->latestActiveEffectiveDate->bonus_rate ?? '';
            $agent->password = '';

            return $agent;
        });

        if (sizeof($statuses) == 1 && in_array('1', $statuses)) {
            $agents = $agents->filter(function ($agent) {
                return $agent->is_active === true;
            })->values();
        } elseif (sizeof($statuses) == 1 && in_array('0', $statuses)) {
            $agents = $agents->filter(function ($agent) {
                return $agent->is_active === false;
            })->values();
        }

        if ($request->filled('product_id')) {
            $agents = $agents->filter(function ($agent) use ($request) {
                return $agent->product_id == $request->input('product_id');
            })->values();
        }

        $allow_list = array_merge([
            'id',
            'agent_name',
            'agent_type_id',
            'company',
            'company_id',
            'company_name',
            'is_active',
            'is_active_icon',
            'email',
            'access_role_id',
            'companies_list',
            'password',
            'date_of_birth',
            'first_performance_date',
        ],
            DialerAgentType::USER !== $agentTypeId && $request->user()->hasAccessToArea("ACCESS_AREA_BILLABLE_RATES") ?
                [
                    'billable_rate',
                    'billable_rate_formatted',
                ] : [],
            DialerAgentType::AGENT !== $agentTypeId ? [
                'access_role_name',
            ] : [],
            DialerAgentType::USER !== $agentTypeId ? [
                'product',
                'product_id',
                'product_name',
                'payment_type',
                'payment_type_id',
                'payable_rate',
                'payable_rate_formatted',
                'bonus_rate',
                'bonus_rate_formatted',
                'effectiveHireDate',
                'team_id',
            ] : [],
            // TODO: IF EXPORTING, ADD EMAIL COLUMN
            DialerAgentType::USER !== $agentTypeId ? !in_array('0', $statuses) ? [
                'payment_type_name',
                'team_name',
            ] : [
                'effectiveTerminationDate',
                'effectiveTerminationReason',
            ] : [],
        );

        $counts = [
            'campaign' => $agents
                ->map(function ($group) {
                    $group = clone $group;
                    $group->product_name = !empty($group->product_name) ? $group->product_name : 'No Campaign';

                    return $group;
                })
                ->groupBy('product_name')
                ->sortKeys()
                ->map(function ($group) {
                    return $group->count();
                }),
            'call_center' => $agents
                ->map(function ($group) {
                    $group = clone $group;
                    $group->company_name = !empty($group->company_name) ? $group->company_name : 'No Call Center';

                    return $group;
                })
                ->groupBy('company_name')
                ->sortKeys()
                ->map(function ($group) {
                    return $group->count();
                }),
            'team' => $agents
                ->map(function ($group) {
                    $group = clone $group;
                    $group->team_name = !empty($group->team_name) ? $group->team_name : 'No Team';

                    return $group;
                })
                ->groupBy('team_name')
                ->sortKeys()
                ->map(function ($group) {
                    return $group->count();
                }),
        ];

        $datatable = [
            'columns' => [
                ['label' => $request->input('title').' ID', 'field' => 'id'],
                ['label' => $request->input('title').' Name', 'field' => 'agent_name'],
                ['label' => 'Campaign', 'field' => 'product_name'],
                ['label' => 'Payment Type', 'field' => 'payment_type_name'],
                ['label' => 'Team', 'field' => 'team_name'],
                ['label' => 'Access Role', 'field' => 'access_role_name'],
                ['label' => 'Billable Rate (US$)', 'field' => 'billable_rate_formatted', 'displayFormat' => 'currency'],
                ['label' => 'Payable Rate (US$)', 'field' => 'payable_rate_formatted', 'displayFormat' => 'currency'],
                ['label' => 'Bonus Rate (US$)', 'field' => 'bonus_rate_formatted', 'displayFormat' => 'currency'],
                ['label' => 'Hire Date', 'field' => 'effectiveHireDate', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Term Date', 'field' => 'effectiveTerminationDate', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Term Reason', 'field' => 'effectiveTerminationReason'],
                ['label' => 'Call Center', 'field' => 'company_name'],
                ['label' => 'Active', 'field' => 'is_active', 'displayFormat' => 'boolean_icon',],
            ],
            'rows' => $agents,
            'counts' => $counts,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "{$request->input('title')} Report.xlsx");
    }

    /**
     * Update an agent
     *
     * @param  Request  $request
     */
    public function update(Request $request, $agentId = null)
    {
        if (!empty($agentId)) {
            $agent = DialerAgent::find($agentId);
            if (!$agent) {
                return ErrorResponse::json('Agent not found', 400);
            }
        } else {
            $agent = new DialerAgent();
        }

        $agentTypeId = self::getAgentTypeId($request->input('title'));

        $validator = Validator::make($request->all(), array_merge([
            'title' => 'bail|required|string',
            'company_id' => 'bail|required|exists:companies,idCompany',
            'agent_name' => 'bail|required|string|max:255',
            'access_role_id' => 'bail|nullable|exists:dialer_access_roles,id',
            'password' => 'bail|nullable|string|max:255',
            'email' => 'bail|nullable|email|max:255',
        ],
            DialerAgentType::USER !== $agentTypeId ? [
                'companies_list' => 'bail|nullable|string',
                'team_id' => 'bail|nullable|exists:dialer_teams,id',
            ] : [],
            DialerAgentType::USER !== $agentTypeId ? [
                'date_of_birth' => 'bail|nullable|date',
            ] : [],
            !$agent->exists && DialerAgentType::USER !== $agentTypeId ? [
                'is_training' => 'bail|nullable|boolean',
                'product_id' => 'bail|required|exists:dialer_products,id',
                'payment_type_id' => 'bail|required|exists:dialer_payment_types,id',
                'billable_rate' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
                'payable_rate' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
                'bonus_rate' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            ] : []));

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        if ($request->filled('email')) {
            $emailExists = DialerAgent::query()
                ->where('email', $request->input('email'))
                ->when($agent->exists, function ($query) use ($agent) {
                    $query->where('id', '<>', $agent->id);
                })
                ->first();

            if ($emailExists) {
                return ErrorResponse::json("The email address is already in use by {$emailExists->agent_name} #{$emailExists->id}.", 400);
            }
        }

        DB::transaction(function () use ($agent, $request, $agentTypeId) {
            $agent->agent_name = $request->input('agent_name');
            $agent->company_id = $request->input('company_id');
            if (DialerAgentType::USER !== $agentTypeId) {
                $agent->team_id = $request->filled('team_id') ? $request->input('team_id') : null;
            }
            if (DialerAgentType::USER !== $agentTypeId) {
                $agent->date_of_birth = $request->filled('date_of_birth') ? $request->input('date_of_birth') : null;
            }
            if ($request->user()->hasAccessToArea("ACCESS_AREA_MANAGE_ACCESS_DETAILS")) {
                $agent->access_role_id = $request->filled('access_role_id') ? $request->input('access_role_id') : null;
                if ($request->filled('password')) {
                    $agent->password = Hash::make($request->input('password'));
                }
            }
            $agent->email = $request->input('email');
            // Manually set the agent ID for employees and users
            if (!$agent->exists && DialerAgentType::AGENT !== $agentTypeId) {
                $agent->id = DialerAgent::query()
                    ->select(DB::raw('MAX(id)+1 AS agent_id'))
                    ->where('id', 'LIKE', '9___')
                    ->pluck('agent_id')
                    ->first();
            }
            $agent->save();

            $agent->companies()->sync(collect($request->filled('companies_list') ? explode(',', $request->input('companies_list')) : []));

            if ($agent->wasRecentlyCreated) {
                $effectiveDate = new DialerAgentEffectiveDate();
                $effectiveDate->agent_id = $agent->id;
                $effectiveDate->is_training = !empty($request->input('is_training')) ? 1 : 0;
                $effectiveDate->agent_type_id = $agentTypeId;
                if (DialerAgentType::USER === $agentTypeId) {
                    $effectiveDate->payment_type_id = DialerPaymentType::SALARY;
                    $effectiveDate->start_date = '2021-01-01';
                } else {
                    $effectiveDate->payment_type_id = $request->input('payment_type_id');
                    $effectiveDate->product_id = $request->input('product_id');
                    if ($request->user()->hasAccessToArea("ACCESS_AREA_BILLABLE_RATES")) {
                        $effectiveDate->billable_rate = !empty($request->input('billable_rate')) ? $request->input('billable_rate') : null;
                    }
                    $effectiveDate->payable_rate = !empty($request->input('payable_rate')) ? $request->input('payable_rate') : null;
                    $effectiveDate->bonus_rate = !empty($request->input('bonus_rate')) ? $request->input('bonus_rate') : null;
                    $effectiveDate->start_date = !empty($request->input('start_date')) ? $request->input('start_date') : null;
                }
                $effectiveDate->save();
            }
        });

        if ($request->boolean('send_welcome_email') && !empty($agent->email)) {
            try {
                ForgotController::generateForgotToken(
                    agent: $agent,
                    welcome_message: true,
                );
            } catch (\Throwable $e) {
                return ErrorResponse::json($e->getMessage(), 400);
            }
        }

        return response([]);
    }

    /**
     * Get default campaign values
     *
     * @param  Request  $request
     */
    public function bulk_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agent_ids' => 'bail|required|array',
            'team_id' => 'bail|nullable|exists:dialer_teams,id',
            'company_id' => 'bail|nullable|exists:companies,idCompany',
            'access_role_id' => 'bail|nullable|exists:dialer_access_roles,id',
            'pip_start_date' => 'bail|nullable|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($request) {
            $agent_ids = $request->input('agent_ids');
            $team_id = $request->input('team_id');
            $company_id = $request->input('company_id');
            $access_role_id = $request->input('access_role_id');
            $pip_start_date = $request->input('pip_start_date');

            foreach ($agent_ids as $agent_id) {
                $agent = DialerAgent::find($agent_id);

                if (!$agent) {
                    return ErrorResponse::json("Agent not found: {$agent_id}", 400);
                }

                if (!empty($team_id)) {
                    $agent->team_id = $team_id;
                }

                if (!empty($company_id)) {
                    $agent->company_id = $company_id;
                }

                if ($request->user()->hasAccessToArea("ACCESS_AREA_MANAGE_ACCESS_DETAILS") && !empty($access_role_id)) {
                    $agent->access_role_id = $access_role_id;
                }

                if (!empty($pip_start_date)) {
                    $item = new DialerAgentPip();
                    $item->reporter_agent_id = $request->user()->id;
                    $item->start_date = $pip_start_date;
                    $item->agent_id = $agent_id;
                    $item->save();
                }

                $agent->save();
            }
        });

        return response([]);
    }

    /**
     * Get default campaign values
     *
     * @param  Request  $request
     * @param $company_id
     * @return array|JsonResponse
     */
    public function campaign_defaults(Request $request, $company_id, $campaign_id): JsonResponse|array
    {
        $validator = Validator::make($request->route()->parameters(), [
            'companyId' => 'bail|nullable|exists:companies,idCompany',
            'campaignId' => 'bail|nullable|exists:dialer_products,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $default = DialerCampaignDefault::query()
            ->where('company_id', $company_id)
            ->where('campaign_id', $campaign_id)
            ->first();

        if (!$default) {
            return [];
        }

        return $default->only(array_merge([
            'id',
            'payment_type_id',
            'payable_rate',
            'bonus_rate',
        ], $request->user()->hasAccessToArea("ACCESS_AREA_BILLABLE_RATES") ? [
            'billable_rate',
        ] : []));
    }

    private function getAgentTypeId($title): int
    {
        switch ($title) {
            case 'Employee':
                $agentTypeId = DialerAgentType::VISIBLE_EMPLOYEE;
                break;

            case 'User':
                $agentTypeId = DialerAgentType::USER;
                break;

            default:
                $agentTypeId = DialerAgentType::AGENT;
                break;
        }

        return $agentTypeId;
    }
}
