<?php

namespace App\Http\Controllers\Api\Agents;

use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DialerAgentController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  Request  $request
     */
    public function index(Request $request, $agent_id)
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'bail|nullable|exists:dialer_agents',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        AuditLog::createFromRequest($request, 'DIALER-AGENT:PROFILE', [
            'agent_id' => $agent_id,
        ]);

        return DialerAgent::query()
            ->where('id', $agent_id)
            ->select([
                'id',
                'agent_name',
                'team_id',
                'email',
            ])
            ->with([
                'team' => function ($query) {
                    $query->select([
                        'id',
                        'name',
                        'manager_agent_id',
                    ])->with([
                        'manager' => function ($query) {
                            $query->select([
                                'id',
                                'agent_name',
                            ]);
                        },
                    ]);
                },
                'latestActiveEffectiveDate' => function ($query) {
                    $query->select([
                        'id',
                        'product_id',
                        'dialer_agent_effective_dates.agent_id',
                        DB::raw('ROUND(payable_rate * 2,2) AS payable_rate_belize'),
                        'agent_type_id',
                    ])->with([
                        'product',
                    ]);
                },
                'leaveRequests' => function ($query) {
                    $query->select([
                        'id',
                        'agent_id',
                        'start_time',
                        'end_time',
                        'notes',
                        'leave_request_type_id',
                        'leave_request_status_id',
                    ]);
                },
            ])
            ->first()
            ->append([
                'effectiveHireDate',
                'vacationAccrued',
                'sickAccrued',
                'ptoAccrued',
                'vacationRemaining',
                'sickRemaining',
                'ptoRemaining',
                'vacationTaken',
                'sickTaken',
                'ptoTaken',
                'vacationPending',
                'sickPending',
                'ptoPending',
            ])
            ->setHidden([
                'effectiveDates',
            ]);
    }
}
