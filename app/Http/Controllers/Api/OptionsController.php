<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\DialerAccessArea;
use App\Models\DialerAccessRole;
use App\Models\DialerAgent;
use App\Models\DialerAgentTerminationReason;
use App\Models\DialerAgentType;
use App\Models\DialerAgentWriteupLevel;
use App\Models\DialerAgentWriteupReason;
use App\Models\DialerDocumentType;
use App\Models\DialerHoliday;
use App\Models\DialerLeaveRequestStatus;
use App\Models\DialerLeaveRequestType;
use App\Models\DialerNotificationType;
use App\Models\DialerPaymentType;
use App\Models\DialerPettyCashLocation;
use App\Models\DialerPettyCashNote;
use App\Models\DialerPettyCashReason;
use App\Models\DialerPettyCashVendor;
use App\Models\DialerPipReason;
use App\Models\DialerPipResolution;
use App\Models\DialerProduct;
use App\Models\DialerTeam;
use App\Models\DialerTeamLead;
use App\Models\User;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OptionsController extends BaseController
{
    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function companies(Request $request)
    {
        return Company::query()
            ->select([
                DB::raw('idCompany AS value'),
                DB::raw('name AS text'),
            ])
            ->where('isCallCenter', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function client_companies(Request $request)
    {
        return Company::query()
            ->select([
                DB::raw('idCompany AS value'),
                DB::raw('name AS text'),
            ])
            ->where('dialer_report_type', Company::DIALER_REPORT_TYPE_BILLABLE)
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function payroll_companies(Request $request)
    {
        return Company::query()
            ->select([
                DB::raw('idCompany AS value'),
                DB::raw('name AS text'),
            ])
            ->whereIn('idCompany', DialerAgent::PAYROLL_COMPANY_IDS)
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_agent_types(Request $request)
    {
        return DialerAgentType::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('name AS text'),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_payment_types(Request $request)
    {
        return DialerPaymentType::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('name AS text'),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_products(Request $request)
    {
        return DialerProduct::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('name AS text'),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_teams(Request $request)
    {
        return DialerTeam::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('name AS text'),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_agents(Request $request)
    {
        return $this->getAgentsByType($request, DialerAgentType::AGENT);
    }

    private function getAgentsByType(Request $request, $agent_type_id)
    {
        // Optionally include the selected agent in the list in case they're no longer active
        $agent = null;
        if ($request->filled('id')) {
            $agent = DialerAgent::query()
                ->select([
                    'dialer_agents.id',
                    'dialer_agents.agent_name',
                ])
                ->where('dialer_agents.id', $request->input('id'));
        }

        $agents = DialerAgent::query()
            ->select([
                'dialer_agents.id',
                'dialer_agents.agent_name',
            ])->whereHas('latestActiveEffectiveDate', function (Builder $query) use ($agent_type_id) {
                $query->where('agent_type_id', $agent_type_id);
            })
            ->isActiveForDate(now(config('settings.timezone.local')))
            ->when(!empty($agent), function ($query) use ($agent) {
                $query->union($agent);
            })
            ->orderBy('agent_name')
            ->get();

        return $agents->values()->map(function ($agent) {
            return [
                'value' => $agent->id,
                'text' => $agent->agent_name,
            ];
        });
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_agents_by_date(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'bail|required|date',
            'end_date' => 'bail|required|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        return DialerAgent::query()
            ->leftJoin('dialer_teams', 'dialer_agents.team_id', 'dialer_teams.id')
            ->isActiveForDateRange($startDate, $endDate)
            ->whereIn('dialer_agent_effective_dates.agent_type_id', [DialerAgentType::AGENT])
            ->when(!$request->user()->hasAccessToArea("ACCESS_AREA_ADD_EDIT_AGENTS"), function ($query) use ($request) {
                $agent = DialerAgent::find($request->user()->id);
                if (!empty($agent->id)) {
                    $query->where(function ($query) use ($agent) {
                        $teamIds = DialerTeamLead::where('agent_id', $agent->id)->select('team_id');

                        $query->where('dialer_teams.manager_agent_id', $agent->id)
                            ->orWhereIn('dialer_teams.id', $teamIds);
                    });
                }
            })
            ->select([
                DB::raw('dialer_agents.id AS value'),
                DB::raw('dialer_agents.agent_name AS text'),
            ])
            ->groupBy('dialer_agents.id')
            ->orderBy('dialer_agents.agent_name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_all_people_by_date(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'bail|required|date',
            'end_date' => 'bail|required|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        return DialerAgent::query()
            ->isActiveForDateRange($startDate, $endDate)
            ->select([
                DB::raw('dialer_agents.id AS value'),
                DB::raw('dialer_agents.agent_name AS text'),
            ])
            ->groupBy('dialer_agents.id')
            ->orderBy('dialer_agents.agent_name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_employees(Request $request)
    {
        return $this->getAgentsByType($request, DialerAgentType::VISIBLE_EMPLOYEE);
    }

    public function dialer_agent_writeup_reasons(Request $request)
    {
        return DialerAgentWriteupReason::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('reason AS text'),
            ])
            ->orderBy('reason')
            ->get();
    }

    public function dialer_agent_writeup_levels(Request $request)
    {
        return DialerAgentWriteupLevel::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('name AS text'),
            ])
            ->orderBy('name')
            ->get();
    }

    public function dialer_petty_cash_reasons(Request $request)
    {
        return DialerPettyCashReason::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('reason AS text'),
            ])
            ->orderBy('reason')
            ->get();
    }

    public function dialer_petty_cash_notes(Request $request)
    {
        return DialerPettyCashNote::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('note AS text'),
            ])
            ->orderBy('note')
            ->get();
    }

    public function dialer_petty_cash_locations(Request $request)
    {
        return DialerPettyCashLocation::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('location AS text'),
            ])
            ->orderBy('location')
            ->get();
    }

    public function dialer_petty_cash_vendors(Request $request)
    {
        return DialerPettyCashVendor::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('name AS text'),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_agents_payroll(Request $request)
    {
        return DialerAgent::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('agent_name AS text'),
            ])
            ->whereIn('company_id', DialerAgent::PAYROLL_COMPANY_IDS)
            ->orderBy('agent_name')
            ->orderBy('note')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function users(Request $request)
    {
        return User::query()
            ->select([
                DB::raw('idUser AS value'),
                DB::raw('fullName AS text'),
            ])
            ->where(function ($query) use ($request) {
                $query->where('isArchived', '0')
                    ->when($request->filled('user_id'), function ($query) use ($request) {
                        $query->orWhere('idUser', $request->input('user_id'));
                    });

            })
            ->orderBy('fullName')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function access_roles(Request $request)
    {
        return DialerAccessRole::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('name AS text'),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function termination_reasons(Request $request)
    {
        return DialerAgentTerminationReason::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('reason AS text'),
            ])
            ->orderBy('reason')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function pip_reasons(Request $request)
    {
        return DialerPipReason::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw('reason AS text'),
            ])
            ->orderBy('reason')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function holidays(Request $request)
    {
        return DialerHoliday::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw("CONCAT(name, ' (',holiday,')') AS text"),
            ])
            ->orderBy('holiday', 'DESC')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function access_areas(Request $request)
    {
        return DialerAccessArea::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw("name AS text"),
                'description',
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function notification_types(Request $request)
    {
        return DialerNotificationType::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw("slug AS text"),
                'description',
            ])
            ->orderBy('text')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function leave_request_statuses(Request $request)
    {
        return DialerLeaveRequestStatus::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw("name AS text"),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function leave_request_types(Request $request)
    {
        return DialerLeaveRequestType::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw("name AS text"),
                'slug',
            ])
            // 8143: Only allow sick requests from users who have access.
            ->when(!$request->user()->hasAccessToArea("ACCESS_AREA_SICK_LEAVE_REQUESTS"), function ($query) use ($request) {
                $query->where('slug', '<>', 'sick');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_agent_statuses(Request $request)
    {
        return [
            ['value' => 1, 'text' => 'Active'],
            ['value' => 0, 'text' => 'Inactive'],
        ];

    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function dialer_attendance_statuses(Request $request)
    {
        // If modifying this, please see form.statuses in AttendanceDetail.vue and $filters in AttendanceDetailDataset.php
        return collect([
            ['value' => 1, 'text' => 'Present'],
            ['value' => 2, 'text' => 'Absent'],
            ['value' => 3, 'text' => 'Late'],
            ['value' => 4, 'text' => 'Left Early'],
        ]);
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function document_types(Request $request, $slug = null)
    {
        $class = null;
        if (!empty($slug) && empty($class = DialerDocumentType::getDocumentableType($slug))) {
            return ErrorResponse::json('Documentable type not found', 400);
        }

        return DialerDocumentType::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw("name AS text"),
            ])
            ->when(!empty($class), function ($query) use ($class) {
                $query->where('documentable_type', $class);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Load key value pairs
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function pip_resolutions(Request $request)
    {
        return DialerPipResolution::query()
            ->select([
                DB::raw('id AS value'),
                DB::raw("resolution AS text"),
            ])
            ->get();
    }
}
