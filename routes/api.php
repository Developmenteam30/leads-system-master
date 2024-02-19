<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [\App\Http\Controllers\Api\LoginController::class, 'index']);
Route::post('/forgot', [\App\Http\Controllers\Api\ForgotController::class, 'index']);
Route::post('/forgot/reset', [\App\Http\Controllers\Api\ForgotController::class, 'reset']);
Route::post('/forgot/validate', [\App\Http\Controllers\Api\ForgotController::class, 'validate']);

if (App::environment('development')) {
    Route::get('/options/access_roles', [\App\Http\Controllers\Api\OptionsController::class, 'access_roles']);
}

/************************************************************************************************************************
 * Routes open to all authenticated users
 ************************************************************************************************************************/
Route::middleware([
    'jwt.auth',
])->group(function () {
    Route::get('/options/companies', [\App\Http\Controllers\Api\OptionsController::class, 'companies']);
    Route::get('/options/dialer_agent_types', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_agent_types']);
    Route::get('/options/dialer_agent_statuses', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_agent_statuses']);
    Route::get('/options/dialer_agents', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_agents']);
    Route::get('/options/dialer_employees', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_employees']);
    Route::get('/options/dialer_products', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_products']);
    Route::get('/options/dialer_teams', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_teams']);
    Route::get('/options/payroll_companies', [\App\Http\Controllers\Api\OptionsController::class, 'payroll_companies']);
    Route::get('/options/users', [\App\Http\Controllers\Api\OptionsController::class, 'users']);

    Route::get('/quick-jump/default', [\App\Http\Controllers\Api\QuickJumpController::class, 'index']);
    Route::get('/quick-jump/month', [\App\Http\Controllers\Api\QuickJumpController::class, 'months']);
    Route::get('/quick-jump/week', [\App\Http\Controllers\Api\QuickJumpController::class, 'weeks']);

    Route::get('/reports/database-status', [\App\Http\Controllers\Api\Reports\DatabaseCountsController::class, 'index']);
});

/************************************************************************************************************************
 * Dashboards
 ************************************************************************************************************************/

Route::middleware(['jwt.auth:ACCESS_AREA_DASHBOARD_REPORT'])->group(function () {
    Route::get('/reports/dashboard', [\App\Http\Controllers\Api\Reports\DashboardController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_DASHBOARDS_TEAM'])->group(function () {
    Route::get('/reports/performance-tracker-team', [\App\Http\Controllers\Api\Reports\PerformanceTrackerTeamController::class, 'index']);
});

/************************************************************************************************************************
 * Menu - Reports
 ************************************************************************************************************************/

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_AGENT_DAILY_STATS'])->group(function () {
    Route::get('/reports/agent-daily-stats', [\App\Http\Controllers\Api\Reports\AgentDailyStatsController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_AGENT_HOURS'])->group(function () {
    Route::get('/reports/agent-hours', [\App\Http\Controllers\Api\Reports\AgentHoursController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_AGENT_HOURS_BY_DAY'])->group(function () {
    Route::get('/reports/agent-hours-by-day', [\App\Http\Controllers\Api\Reports\AgentHoursByDayController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_ATTENDANCE'])->group(function () {
    Route::get('/reports/attendance', [\App\Http\Controllers\Api\Reports\AttendanceController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_ATTENDANCE_DETAIL'])->group(function () {
    Route::get('/reports/attendance-detail', [\App\Http\Controllers\Api\Reports\AttendanceDetailController::class, 'index']);
    Route::get('/options/dialer_attendance_statuses', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_attendance_statuses']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_CALL_CENTER_HOURS'])->group(function () {
    Route::get('/reports/call-center-hours', [\App\Http\Controllers\Api\Reports\CallCenterHoursController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_CLIENT_HOURS'])->group(function () {
    Route::get('/reports/client-hours', [\App\Http\Controllers\Api\Reports\ClientHoursController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_DISPOSITIONS'])->group(function () {
    Route::get('/reports/dispositions', [\App\Http\Controllers\Api\Reports\DispositionsController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_LICENSE_SWAP'])->group(function () {
    Route::get('/reports/license-swap', [\App\Http\Controllers\Api\Reports\LicenseSwapController::class, 'index']);
    Route::post('/reports/license-swap/email', [\App\Http\Controllers\Api\Reports\LicenseSwapController::class, 'email']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_LICENSING'])->group(function () {
    Route::get('/reports/licensing', [\App\Http\Controllers\Api\Reports\LicensingController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_ONSCRIPT_UPLOAD_LOGS'])->group(function () {
    Route::get('/reports/onscript-upload-logs', [\App\Http\Controllers\Api\Reports\OnScriptUploadLogsController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_PAYROLL'])->group(function () {
    Route::get('/reports/payroll', [\App\Http\Controllers\Api\Reports\PayrollController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_PAYROLL_EXCEPTIONS'])->group(function () {
    Route::get('/reports/payroll/exceptions', [\App\Http\Controllers\Api\Reports\PayrollExceptionsController::class, 'index']);
    Route::get('/reports/payroll/holiday-warnings', [\App\Http\Controllers\Api\Reports\HolidayWarningsController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_EMAIL_PAYROLL_REPORT'])->group(function () {
    Route::post('/reports/payroll/email', [\App\Http\Controllers\Api\Reports\PayrollController::class, 'email']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_RECALCULATE_PAYROLL'])->group(function () {
    Route::post('/reports/payroll/recalculate', [\App\Http\Controllers\Api\Reports\PayrollController::class, 'recalculate']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_PERFORMANCE'])->group(function () {
    Route::get('/reports/performance', [\App\Http\Controllers\Api\Reports\PerformanceController::class, 'index']);
    Route::get('/reports/performance/details', [\App\Http\Controllers\Api\Reports\PerformanceController::class, 'details']);

    Route::get('/options/dialer_agents/by_date', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_agents_by_date']);
});

Route::middleware([
    'jwt.auth:ACCESS_AREA_MENU_REPORTS_PERFORMANCE_TRACKER,ACCESS_AREA_MENU_DASHBOARDS_AGENT',
    'jwt.logged_in_user:ACCESS_AREA_MENU_REPORTS_PERFORMANCE_TRACKER',
])->group(function () {
    Route::get('/reports/performance-tracker', [\App\Http\Controllers\Api\Reports\PerformanceTrackerController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_PERFORMANCE_TRACKER'])->group(function () {
    Route::get('/reports/performance-tracker/lowest-transfers', [\App\Http\Controllers\Api\Reports\PerformanceTrackerController::class, 'lowestTransfers']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_REPORTS_PERFORMANCE_TRACKER_OVERVIEW'])->group(function () {
    Route::get('/reports/performance-tracker-overview', [\App\Http\Controllers\Api\Reports\PerformanceTrackerOverviewController::class, 'index']);
});


/************************************************************************************************************************
 * Menu - Uploads
 ************************************************************************************************************************/

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_UPLOADS'])->group(function () {
    Route::post('/uploads/retreaver', [\App\Http\Controllers\Api\Uploads\RetreaverUploadController::class, 'store']);
    Route::post('/uploads/call-detail-log', [\App\Http\Controllers\Api\Uploads\CallDetailLogUploadController::class, 'store']);
    Route::post('/uploads/agent-performance', [\App\Http\Controllers\Api\Uploads\AgentPerformanceUploadController::class, 'store']);
});


/************************************************************************************************************************
 * Menu - People
 ************************************************************************************************************************/

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_PEOPLE_AGENTS'])->group(function () {
    Route::get('/agents/manage', [\App\Http\Controllers\Api\Agents\ManageController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_ADD_EDIT_EMPLOYEES,ACCESS_AREA_ADD_EDIT_USERS'])->group(function () {
    Route::post('/agents/manage', [\App\Http\Controllers\Api\Agents\ManageController::class, 'update']);
    Route::patch('/agents/manage/{id}', [\App\Http\Controllers\Api\Agents\ManageController::class, 'update']);

    Route::get('/agents/campaign-defaults/{companyId}/{campaignId}', [\App\Http\Controllers\Api\Agents\ManageController::class, 'campaign_defaults']);

    Route::get('/agents/effective-dates/{agentId}', [\App\Http\Controllers\Api\Agents\EffectiveDatesController::class, 'index']);
    Route::get('/agents/effective-dates/{agentId}/open-ended', [\App\Http\Controllers\Api\Agents\EffectiveDatesController::class, 'openEnded']);
    Route::post('/agents/effective-dates', [\App\Http\Controllers\Api\Agents\EffectiveDatesController::class, 'update']);
    Route::patch('/agents/effective-dates/{id}', [\App\Http\Controllers\Api\Agents\EffectiveDatesController::class, 'update']);

    if (!App::environment('development')) {
        Route::get('/options/access_roles', [\App\Http\Controllers\Api\OptionsController::class, 'access_roles']);
    }
    Route::get('/options/client_companies', [\App\Http\Controllers\Api\OptionsController::class, 'client_companies']);
    Route::get('/options/dialer_payment_types', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_payment_types']);
    Route::get('/options/termination_reasons', [\App\Http\Controllers\Api\OptionsController::class, 'termination_reasons']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_AGENT_BULK_EDIT'])->group(function () {
    Route::patch('/agents/bulk-manage', [\App\Http\Controllers\Api\Agents\ManageController::class, 'bulk_update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_PEOPLE_EOD_REPORTS'])->group(function () {
    Route::get('/reports/endofday/manage', [\App\Http\Controllers\Api\Reports\EndOfDayController::class, 'index']);

    Route::get('/reports/endofday/manage/{item}', function (\App\Models\DialerEodReport $item) {
        return $item;
    })->withTrashed();

    Route::post('/reports/endofday/manage', [\App\Http\Controllers\Api\Reports\EndOfDayController::class, 'update']);
    Route::patch('/reports/endofday/manage/{id}', [\App\Http\Controllers\Api\Reports\EndOfDayController::class, 'update']);
});

Route::middleware([
    'jwt.auth:ACCESS_AREA_MENU_PEOPLE_DOCUMENTS,ACCESS_AREA_MENU_DASHBOARDS_AGENT,ACCESS_AREA_MENU_DASHBOARDS_EMPLOYEE',
    'jwt.logged_in_user:ACCESS_AREA_MENU_PEOPLE_DOCUMENTS',
])->group(function () {
    Route::get('/agent/{agent_id}', [\App\Http\Controllers\Api\Agents\DialerAgentController::class, 'index']);

    Route::get('/documents/agent/{agent_id}', [\App\Http\Controllers\Api\Reports\DocumentsController::class, 'agent']);
    Route::post('/documents/agent/{agent_id}', [\App\Http\Controllers\Api\Uploads\DocumentUploadController::class, 'agent']);

    Route::get('/options/document-types', [\App\Http\Controllers\Api\OptionsController::class, 'document_types']);
    Route::get('/options/document-types/{slug}', [\App\Http\Controllers\Api\OptionsController::class, 'document_types']);

    Route::get('/documents', [\App\Http\Controllers\Api\Reports\DocumentsController::class, 'index']);
    Route::get('/documents/{item}', function (\App\Models\DialerDocument $item) {
        return $item->load([
            'documentType' => function ($query) {
                $query->select('id', 'name');
            },
            'documentable' => function ($query) {
                $query->select('id', 'agent_name');
            },
        ]);
    })->withTrashed();
    Route::delete('/documents/{id}', [\App\Http\Controllers\Api\Reports\DocumentsController::class, 'delete']);
    Route::patch('/documents/{id}/restore', [\App\Http\Controllers\Api\Reports\DocumentsController::class, 'restore']);
});

Route::middleware([
    'jwt.auth:ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_MENU_DASHBOARDS_AGENT,ACCESS_AREA_MENU_DASHBOARDS_EMPLOYEE,ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS',
    'jwt.logged_in_user:ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS',
])->group(function () {
    Route::get('/leave-requests/agent/{agent_id}', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestController::class, 'index']);
    Route::get('/leave-requests/agent/{agent}/time_available', function (\App\Models\DialerAgent $agent) {
        $columns = [
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
        ];

        return $agent
            ->append($columns)
            ->only($columns);
    });
});

Route::middleware([
    'jwt.auth:ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_MENU_DASHBOARDS_AGENT,ACCESS_AREA_MENU_DASHBOARDS_EMPLOYEE,ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS',
])->group(function () {
    Route::get('/options/leave_request_statuses', [\App\Http\Controllers\Api\OptionsController::class, 'leave_request_statuses']);
    Route::get('/options/leave_request_types', [\App\Http\Controllers\Api\OptionsController::class, 'leave_request_types']);
});

Route::middleware([
    'jwt.auth:ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_MENU_DASHBOARDS_AGENT,ACCESS_AREA_MENU_DASHBOARDS_EMPLOYEE,ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS',
    'jwt.logged_in_user:ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS',
])->group(function () {
    Route::get('/leave-requests', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestController::class, 'index']);

    Route::get('/leave-requests/{item}', function (\App\Models\DialerLeaveRequest $item) {
        return $item->load([
            'agent' => function ($query) {
                $query->select('id', 'agent_name');
            },
            'type' => function ($query) {
                $query->select('id', 'name');
            },
            'documents',
        ]);
    })->withTrashed();
    Route::post('/leave-requests', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestController::class, 'update']);
    Route::patch('/leave-requests/{id}', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestController::class, 'update']);
    Route::delete('/leave-requests/{id}', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestController::class, 'delete']);

    Route::get('/documents/leave-request/{id}', [\App\Http\Controllers\Api\Reports\DocumentsController::class, 'index']);
    Route::post('/documents/leave-request/{id}', [\App\Http\Controllers\Api\Uploads\DocumentUploadController::class, 'leave_request']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_PEOPLE_EVALUATIONS'])->group(function () {
    Route::get('/evaluations/manage', [\App\Http\Controllers\Api\Evaluations\EvaluationsController::class, 'index']);
    Route::get('/evaluations/manage/{item}', function (\App\Models\DialerAgentEvaluation $item) {
        return $item->load([
            'agent' => function ($query) {
                $query->select('id', 'agent_name');
            },
        ]);
    })->withTrashed();
    Route::post('/evaluations/manage', [\App\Http\Controllers\Api\Evaluations\EvaluationsController::class, 'update']);
    Route::patch('/evaluations/manage/{id}', [\App\Http\Controllers\Api\Evaluations\EvaluationsController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_EVALUATIONS_EDIT'])->group(function () {
    Route::delete('/evaluations/manage/{id}', [\App\Http\Controllers\Api\Evaluations\EvaluationsController::class, 'delete']);
    Route::patch('/evaluations/manage/{id}/restore', [\App\Http\Controllers\Api\Evaluations\EvaluationsController::class, 'restore']);
});
Route::middleware([
    'jwt.auth:ACCESS_AREA_MENU_PEOPLE_EVALUATIONS,ACCESS_AREA_MENU_DASHBOARDS_AGENT,ACCESS_AREA_MENU_DASHBOARDS_EMPLOYEE,ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_ADD_EDIT_EMPLOYEES',
    'jwt.logged_in_user:ACCESS_AREA_MENU_PEOPLE_EVALUATIONS,ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_ADD_EDIT_EMPLOYEES',
])->group(function () {
    Route::get('/evaluations/agent/{agent_id}', [\App\Http\Controllers\Api\Evaluations\EvaluationsController::class, 'index']);
});

Route::middleware([
    'jwt.auth:ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_MENU_DASHBOARDS_AGENT,ACCESS_AREA_MENU_DASHBOARDS_EMPLOYEE,ACCESS_AREA_MENU_PEOPLE_PIPS',
    'jwt.logged_in_user:ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_MENU_PEOPLE_PIPS',
])->group(function () {
    Route::get('/pips/manage', [\App\Http\Controllers\Api\Pips\PipManageController::class, 'index']);
    Route::get('/pips/manage/{item}', function (\App\Models\DialerAgentPip $item) {
        return $item->load([
            'agent' => function ($query) {
                $query->select('id', 'agent_name');
            },
            'resolution' => function ($query) {
                $query->select('id', 'resolution');
            },
        ])->append([
            'reason_ids',
            'reasons_string',
        ]);
    })->withTrashed();
    Route::patch('/pips/manage/{id}', [\App\Http\Controllers\Api\Pips\PipManageController::class, 'update']);
    Route::post('/pips/manage', [\App\Http\Controllers\Api\Pips\PipManageController::class, 'update']);
    Route::patch('/pips/manage/{id}/restore', [\App\Http\Controllers\Api\Pips\PipManageController::class, 'restore']);

    Route::get('/options/pip_reasons', [\App\Http\Controllers\Api\OptionsController::class, 'pip_reasons']);
    Route::get('/options/pip_resolutions', [\App\Http\Controllers\Api\OptionsController::class, 'pip_resolutions']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_PIPS_EDIT'])->group(function () {
    Route::delete('/pips/manage/{id}', [\App\Http\Controllers\Api\Pips\PipManageController::class, 'delete']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_PEOPLE_TEAMS'])->group(function () {
    Route::get('/teams/manage', [\App\Http\Controllers\Api\Teams\ManageController::class, 'index']);
    Route::get('/teams/manage/{item}', function (\App\Models\DialerTeam $item) {
        return $item->append([
            'team_lead_agent_ids',
            'team_lead_agent_names_string',
        ]);
    })->withTrashed();
    Route::post('/teams/manage', [\App\Http\Controllers\Api\Teams\ManageController::class, 'update']);
    Route::patch('/teams/manage/{id}', [\App\Http\Controllers\Api\Teams\ManageController::class, 'update']);
    Route::get('/teams/members/{teamId}', [\App\Http\Controllers\Api\Teams\ManageController::class, 'members']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_PEOPLE_TERMINATION_LOG'])->group(function () {
    Route::get('/terminations/manage', [\App\Http\Controllers\Api\Terminations\ManageController::class, 'index']);
    Route::get('/terminations/manage/{item}', function (\App\Models\DialerAgentTermination $item) {
        return $item->load([
            'agent' => function ($query) {
                $query->select('id', 'agent_name');
            },
            'reason' => function ($query) {
                $query->select('id', 'reason');
            },
            'nominator' => function ($query) {
                $query->select('id', 'agent_name');
            },
        ]);
    });
    Route::post('/terminations/manage', [\App\Http\Controllers\Api\Terminations\ManageController::class, 'update']);
    Route::patch('/terminations/manage/{id}', [\App\Http\Controllers\Api\Terminations\ManageController::class, 'update']);

    Route::get('/options/termination_reasons', [\App\Http\Controllers\Api\OptionsController::class, 'termination_reasons']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_EDIT_TERMINATIONS'])->group(function () {
    Route::delete('/terminations/manage/{id}', [\App\Http\Controllers\Api\Terminations\ManageController::class, 'delete']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_PEOPLE_WRITEUPS_AGENTS,ACCESS_AREA_MENU_PEOPLE_WRITEUPS_EMPLOYEES'])->group(function () {
    Route::get('/writeups/manage', [\App\Http\Controllers\Api\Writeups\ManageController::class, 'index']);
    Route::get('/writeups/manage/{item}', function (\App\Models\DialerAgentWriteup $item) { // TODO: ALLOW AGENTS TO SEE THEIR OWN WRITEUPS
        return $item->load([
            'agent' => function ($query) {
                $query->select('id', 'agent_name');
            },
            'reason' => function ($query) {
                $query->select('id', 'reason');
            },
            'reporter' => function ($query) {
                $query->select('id', 'agent_name');
            },
            'level' => function ($query) {
                $query->select('id', 'name');
            },
        ]);
    });
    Route::post('/writeups/manage', [\App\Http\Controllers\Api\Writeups\ManageController::class, 'update']);
    Route::patch('/writeups/manage/{id}', [\App\Http\Controllers\Api\Writeups\ManageController::class, 'update']);
    Route::get('/writeups/team/{id}', [\App\Http\Controllers\Api\Writeups\ManageController::class, 'team']);

    Route::get('/options/dialer_agent_writeup_reasons', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_agent_writeup_reasons']);
    Route::get('/options/dialer_agent_writeup_levels', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_agent_writeup_levels']);
});

Route::middleware([
    'jwt.auth:ACCESS_AREA_MENU_PEOPLE_WRITEUPS_AGENTS,ACCESS_AREA_MENU_PEOPLE_WRITEUPS_EMPLOYEES,ACCESS_AREA_MENU_DASHBOARDS_AGENT,ACCESS_AREA_MENU_DASHBOARDS_EMPLOYEE,ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_ADD_EDIT_EMPLOYEES',
    'jwt.logged_in_user:ACCESS_AREA_MENU_PEOPLE_WRITEUPS_AGENTS,ACCESS_AREA_MENU_PEOPLE_WRITEUPS_EMPLOYEES,ACCESS_AREA_ADD_EDIT_AGENTS,ACCESS_AREA_ADD_EDIT_EMPLOYEES',
])->group(function () {
    Route::get('/writeups/agent/{agent_id}', [\App\Http\Controllers\Api\Writeups\ManageController::class, 'index']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_WRITEUPS_EDIT'])->group(function () {
    Route::delete('/writeups/manage/{id}', [\App\Http\Controllers\Api\Writeups\ManageController::class, 'delete']);
});

/************************************************************************************************************************
 * Menu - Admin
 ************************************************************************************************************************/

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_ACCESS_AREAS'])->group(function () {
    Route::get('/access-area/manage', [\App\Http\Controllers\Api\Admin\DialerAccessAreaController::class, 'index']);
    Route::get('/access-area/manage/{item}', function (\App\Models\DialerAccessArea $item) {
        return $item;
    });
    Route::post('/access-area/manage', [\App\Http\Controllers\Api\Admin\DialerAccessAreaController::class, 'update']);
    Route::patch('/access-area/manage/{id}', [\App\Http\Controllers\Api\Admin\DialerAccessAreaController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_ACCESS_AREAS,ACCESS_AREA_MENU_ADMIN_ROLES'])->group(function () {
    Route::get('/options/access_areas', [\App\Http\Controllers\Api\OptionsController::class, 'access_areas']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_CAMPAIGNS'])->group(function () {
    Route::get('/campaigns/manage', [\App\Http\Controllers\Api\Campaigns\CampaignsController::class, 'index']);
    Route::post('/campaigns/manage', [\App\Http\Controllers\Api\Campaigns\CampaignsController::class, 'update']);
    Route::patch('/campaigns/manage/{id}', [\App\Http\Controllers\Api\Campaigns\CampaignsController::class, 'update']);

    Route::get('/campaigns/companies/{id}', [\App\Http\Controllers\Api\Campaigns\CampaignDefaultsController::class, 'index']);
    Route::post('/campaigns/companies', [\App\Http\Controllers\Api\Campaigns\CampaignDefaultsController::class, 'update']);
    Route::patch('/campaigns/companies/{id}', [\App\Http\Controllers\Api\Campaigns\CampaignDefaultsController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_DOCUMENT_TYPES'])->group(function () {
    Route::get('/document-types/manage', [\App\Http\Controllers\Api\DocumentTypes\DocumentTypesController::class, 'index']);
    Route::get('/document-types/manage/{item}', function (\App\Models\DialerDocumentType $item) {
        return $item;
    })->withTrashed();
    Route::post('/document-types/manage', [\App\Http\Controllers\Api\DocumentTypes\DocumentTypesController::class, 'update']);
    Route::patch('/document-types/manage/{id}', [\App\Http\Controllers\Api\DocumentTypes\DocumentTypesController::class, 'update']);
    Route::delete('/document-types/manage/{id}', [\App\Http\Controllers\Api\DocumentTypes\DocumentTypesController::class, 'delete']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_EXTERNAL_CAMPAIGNS'])->group(function () {
    Route::get('/external-campaigns/manage', [\App\Http\Controllers\Api\Admin\DialerExternalCampaignController::class, 'index']);
    Route::get('/external-campaigns/manage/{item}', function (\App\Models\DialerExternalCampaign $item) {
        return $item;
    })->withTrashed();
    Route::post('/external-campaigns/manage', [\App\Http\Controllers\Api\Admin\DialerExternalCampaignController::class, 'update']);
    Route::patch('/external-campaigns/manage/{id}', [\App\Http\Controllers\Api\Admin\DialerExternalCampaignController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_HOLIDAYS'])->group(function () {
    Route::get('/holiday-lists/manage', [\App\Http\Controllers\Api\Holidays\HolidayListsController::class, 'index']);
    Route::post('/holiday-lists/manage', [\App\Http\Controllers\Api\Holidays\HolidayListsController::class, 'update']);
    Route::patch('/holiday-lists/manage/{id}', [\App\Http\Controllers\Api\Holidays\HolidayListsController::class, 'update']);
    Route::get('/holiday-lists/manage/{item}', function (\App\Models\DialerHolidayList $item) {
        return $item->append([
            'holidays_ids',
            'holidays_string',
        ]);
    });
    Route::get('/holidays/manage', [\App\Http\Controllers\Api\Holidays\HolidaysController::class, 'index']);
    Route::get('/holidays/manage/{item}', function (\App\Models\DialerHoliday $item) {
        return $item;
    });
    Route::post('/holidays/manage', [\App\Http\Controllers\Api\Holidays\HolidaysController::class, 'update']);
    Route::patch('/holidays/manage/{id}', [\App\Http\Controllers\Api\Holidays\HolidaysController::class, 'update']);
    Route::get('/options/holidays', [\App\Http\Controllers\Api\OptionsController::class, 'holidays']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_NOTIFICATION_TYPES'])->group(function () {
    Route::get('/notification-type/manage', [\App\Http\Controllers\Api\Admin\DialerNotificationTypeController::class, 'index']);
    Route::get('/notification-type/manage/{item}', function (\App\Models\DialerNotificationType $item) {
        return $item;
    });
    Route::post('/notification-type/manage', [\App\Http\Controllers\Api\Admin\DialerNotificationTypeController::class, 'update']);
    Route::patch('/notification-type/manage/{id}', [\App\Http\Controllers\Api\Admin\DialerNotificationTypeController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_NOTIFICATION_TYPES,ACCESS_AREA_MENU_ADMIN_ROLES'])->group(function () {
    Route::get('/options/notification_types', [\App\Http\Controllers\Api\OptionsController::class, 'notification_types']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_LEAVE_REQUEST_STATUSES'])->group(function () {
    Route::get('/leave-request-statuses/manage', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestStatusesController::class, 'index']);
    Route::post('/leave-request-statuses/manage', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestStatusesController::class, 'update']);
    Route::patch('/leave-request-statuses/manage/{id}', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestStatusesController::class, 'update']);
    Route::delete('/leave-request-statuses/manage/{id}', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestStatusesController::class, 'delete']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_LEAVE_REQUEST_TYPES'])->group(function () {
    Route::get('/leave-request-types/manage', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestTypesController::class, 'index']);
    Route::get('/leave-request-types/manage/{item}', function (\App\Models\DialerLeaveRequestType $item) {
        return $item;
    })->withTrashed();
    Route::post('/leave-request-types/manage', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestTypesController::class, 'update']);
    Route::patch('/leave-request-types/manage/{id}', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestTypesController::class, 'update']);
    Route::delete('/leave-request-types/manage/{id}', [\App\Http\Controllers\Api\LeaveRequests\LeaveRequestTypesController::class, 'delete']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_PETTY_CASH'])->group(function () {
    Route::get('/petty-cash-reasons/manage', [\App\Http\Controllers\Api\PettyCashReasons\PettyCashReasonsController::class, 'index']);
    Route::get('/petty-cash-reasons/manage/{item}', function (\App\Models\DialerPettyCashReason $item) {
        return $item;
    })->withTrashed();
    Route::post('/petty-cash-reasons/manage', [\App\Http\Controllers\Api\PettyCashReasons\PettyCashReasonsController::class, 'update']);
    Route::patch('/petty-cash-reasons/manage/{id}', [\App\Http\Controllers\Api\PettyCashReasons\PettyCashReasonsController::class, 'update']);
    Route::delete('/petty-cash-reasons/manage/{id}', [\App\Http\Controllers\Api\PettyCashReasons\PettyCashReasonsController::class, 'delete']);

    Route::get('/options/petty-cash-reasons', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_petty_cash_reasons']);
    Route::get('/options/petty-cash-locations', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_petty_cash_locations']);
    Route::get('/options/petty-cash-notes', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_petty_cash_notes']);
    Route::get('/options/petty-cash-vendors', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_petty_cash_vendors']);

    Route::get('/petty-cash/manage', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashEntriesController::class, 'index']);
    Route::get('/petty-cash/manage/{item}', function (\App\Models\DialerPettyCashEntry $item) {
        return $item;
    })->withTrashed();
    Route::post('/petty-cash/manage', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashEntriesController::class, 'update']);
    Route::patch('/petty-cash/manage/{id}', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashEntriesController::class, 'update']);

    Route::get('/petty-cash-locations/manage', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashLocationsController::class, 'index']);
    Route::get('/petty-cash-locations/manage/{item}', function (\App\Models\DialerPettyCashLocation $item) {
        return $item;
    })->withTrashed();
    Route::post('/petty-cash-locations/manage', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashLocationsController::class, 'update']);
    Route::patch('/petty-cash-locations/manage/{id}', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashLocationsController::class, 'update']);
    Route::delete('/petty-cash-locations/manage/{id}', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashLocationsController::class, 'delete']);

    Route::get('/petty-cash-notes/manage', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashNotesController::class, 'index']);
    Route::get('/petty-cash-notes/manage/{item}', function (\App\Models\DialerPettyCashNote $item) {
        return $item;
    })->withTrashed();
    Route::post('/petty-cash-notes/manage', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashNotesController::class, 'update']);
    Route::patch('/petty-cash-notes/manage/{id}', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashNotesController::class, 'update']);
    Route::delete('/petty-cash-notes/manage/{id}', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashNotesController::class, 'delete']);

    Route::get('/petty-cash-vendors/manage', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashVendorsController::class, 'index']);
    Route::get('/petty-cash-vendors/manage/{item}', function (\App\Models\DialerPettyCashVendor $item) {
        return $item;
    })->withTrashed();
    Route::post('/petty-cash-vendors/manage', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashVendorsController::class, 'update']);
    Route::patch('/petty-cash-vendors/manage/{id}', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashVendorsController::class, 'update']);
    Route::delete('/petty-cash-vendors/manage/{id}', [\App\Http\Controllers\Api\PettyCashEntries\PettyCashVendorsController::class, 'delete']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_JOB_QUEUE'])->group(function () {
    Route::get('/job-queue', [\App\Http\Controllers\Api\JobQueueController::class, 'index']);
    Route::post('/job-queue/retry/{logId}', [\App\Http\Controllers\Api\JobQueueController::class, 'retry']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_ROLES'])->group(function () {
    Route::get('/roles/manage', [\App\Http\Controllers\Api\Roles\RolesController::class, 'index']);
    Route::get('/roles/manage/{item}', function (\App\Models\DialerAccessRole $item) {
        return $item->append([
            'accessAreasList',
            'notificationTypesList',
        ]);
    });
    Route::post('/roles/manage', [\App\Http\Controllers\Api\Roles\RolesController::class, 'update']);
    Route::patch('/roles/manage/{id}', [\App\Http\Controllers\Api\Roles\RolesController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_PIP_REASONS'])->group(function () {
    Route::get('/pip-reasons/manage', [\App\Http\Controllers\Api\PipReasons\PipReasonsController::class, 'index']);
    Route::post('/pip-reasons/manage', [\App\Http\Controllers\Api\PipReasons\PipReasonsController::class, 'update']);
    Route::patch('/pip-reasons/manage/{id}', [\App\Http\Controllers\Api\PipReasons\PipReasonsController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_TERMINATION_REASONS'])->group(function () {
    Route::get('/termination-reasons/manage', [\App\Http\Controllers\Api\TerminationReasons\TerminationReasonsController::class, 'index']);
    Route::post('/termination-reasons/manage', [\App\Http\Controllers\Api\TerminationReasons\TerminationReasonsController::class, 'update']);
    Route::patch('/termination-reasons/manage/{id}', [\App\Http\Controllers\Api\TerminationReasons\TerminationReasonsController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_WRITEUP_LEVELS'])->group(function () {
    Route::get('/writeup-levels/manage', [\App\Http\Controllers\Api\WriteupLevels\WriteupLevelsController::class, 'index']);
    Route::post('/writeup-levels/manage', [\App\Http\Controllers\Api\WriteupLevels\WriteupLevelsController::class, 'update']);
    Route::patch('/writeup-levels/manage/{id}', [\App\Http\Controllers\Api\WriteupLevels\WriteupLevelsController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_WRITEUP_REASONS'])->group(function () {
    Route::get('/writeup-reasons/manage', [\App\Http\Controllers\Api\WriteupReasons\WriteupReasonsController::class, 'index']);
    Route::post('/writeup-reasons/manage', [\App\Http\Controllers\Api\WriteupReasons\WriteupReasonsController::class, 'update']);
    Route::patch('/writeup-reasons/manage/{id}', [\App\Http\Controllers\Api\WriteupReasons\WriteupReasonsController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_MENU_ADMIN_AUDIT_LOG'])->group(function () {
    Route::get('/options/dialer_all_people/by_date', [\App\Http\Controllers\Api\OptionsController::class, 'dialer_all_people_by_date']);

    Route::get('/audit-log/manage/', [\App\Http\Controllers\Api\Admin\AuditLogController::class, 'index']);
    Route::get('/audit-log/manage/{item}', function (\App\Models\AuditLog $item) {
        if (!empty($item->notes)) {
            $item->notes = json_decode($item->notes);
        } else {
            $item->notes = [];
        }

        return $item;
    })->withTrashed();
});


/************************************************************************************************************************
 * Miscellaneous
 ************************************************************************************************************************/

Route::middleware(['jwt.auth:ACCESS_AREA_AGENT_HOURS_BULK_EDIT'])->group(function () {
    Route::patch('/reports/payroll/manage/bulk-update', [\App\Http\Controllers\Api\Reports\PayrollController::class, 'bulk_update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_EDIT_AGENT_HOURS'])->group(function () {
    Route::patch('/reports/payroll/{id}', [\App\Http\Controllers\Api\Reports\PayrollController::class, 'update']);
});

Route::middleware(['jwt.auth:ACCESS_AREA_LOGIN_IMPERSONATION'])->group(function () {
    Route::post('/login/impersonate/{agent_id}', [\App\Http\Controllers\Api\LoginController::class, 'impersonate']);
});
