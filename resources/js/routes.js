import {createRouter, createWebHistory} from "vue-router";
import {authStore} from "@/store/auth-store";

const routes = [
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import("./errors/NotFound.vue"),
    },
    {
        path: '/',
        component: () => import("./views/AuthenticatedBase.vue"),
        beforeEnter: (to, from, next) => {
            if (!authStore.isLoggedIn()) {
                next({name: 'login'});
            } else {
                next();
            }
        },
        children: [
            {
                path: '/',
                name: 'dashboard',
                component: () => import("./views/Home.vue"),
            },
            {
                path: '/dashboards/agent',
                name: 'dashboards-agent',
                component: () => import("./views/dashboards/AgentDashboard.vue"),
            },
            {
                path: '/dashboards/employee',
                name: 'dashboards-employee',
                component: () => import("./views/dashboards/EmployeeDashboard.vue"),
            },
            {
                path: '/dashboards/management',
                name: 'dashboards-management',
                component: () => import("./views/dashboards/ManagementDashboard.vue"),
            },
            {
                path: '/dashboards/team',
                name: 'dashboards-team',
                component: () => import("./views/dashboards/TeamDashboard.vue"),
            },
            {
                path: '/agents/manage',
                name: 'agents-manage',
                component: () => import("./views/agents/AgentListStub.vue"),
            },
            {
                path: '/agents/bulk-manage',
                name: 'agents-bulk-manage',
                component: () => import("./views/agents/AgentListStub.vue"),
            },
            {
                path: '/people/documents',
                name: 'people-documents',
                component: () => import("./views/documents/DocumentList.vue"),
            },
           {
                path: '/people/eod-reports',
                name: 'people-eod-reports',
                component: () => import("./views/reports/EndOfDay.vue"),
            },
            {
                path: '/people/leave-requests',
                name: 'people-leave-requests',
                component: () => import("./views/leave-requests/LeaveRequestListAgents.vue"),
            },
            {
                path: '/employees/manage',
                name: 'employees-manage',
                component: () => import("./views/employees/EmployeeListStub.vue"),
            },
            {
                path: '/users/manage',
                name: 'users-manage',
                component: () => import("./views/users/UserListStub.vue"),
            },
            {
                path: '/evaluations/manage',
                name: 'evaluations-manage',
                component: () => import("./views/evaluations/EvaluationList.vue"),
            },
            {
                path: '/pips/manage',
                name: 'pips-manage',
                component: () => import("./views/people/pips/PipList.vue"),
            },
            {
                path: '/teams/manage',
                name: 'teams-manage',
                component: () => import("./views/teams/TeamList.vue"),
            },
            {
                path: '/people/termination-log',
                name: 'termination-log',
                component: () => import("./views/agents/AgentTermination.vue"),
            },
            {
                path: '/writeups/manage-agents',
                name: 'writeups-manage-agents',
                component: () => import("./views/writeups/WriteupListAgents.vue"),
            },
            {
                path: '/writeups/manage-employees',
                name: 'writeups-manage-employees',
                component: () => import("./views/writeups/WriteupListEmployees.vue"),
            },
            {
                path: '/admin/internal-campaigns',
                name: 'admin-internal-campaigns',
                component: () => import("./views/admin/campaigns/CampaignList.vue"),
            },
            {
                path: '/admin/external-campaigns',
                name: 'admin-external-campaigns',
                component: () => import("./views/admin/external-campaigns/ExternalCampaignsList.vue"),
            },
            {
                path: '/admin/job-queue',
                name: 'admin-job-queue',
                component: () => import("./views/JobQueue.vue"),
            },
            {
                path: '/admin/roles',
                name: 'admin-roles',
                component: () => import("./views/admin/roles/RoleList.vue"),
            },
            {
                path: '/admin/access-area',
                name: 'admin-access-area',
                component: () => import("./views/admin/access-areas/AccessAreaList.vue"),
            },
            {
                path: '/admin/document-types',
                name: 'admin-document-types',
                component: () => import("./views/admin/documents/DocumentTypesList.vue"),
            },
            {
                path: '/admin/holidays',
                name: 'admin-holidays',
                component: () => import("./views/admin/holidays/Holidays.vue"),
            },
            {
                path: '/admin/holiday-lists',
                name: 'admin-holiday-lists',
                component: () => import("./views/admin/holidays/HolidayLists.vue"),
            },
            {
                path: '/admin/leave-request-statuses',
                name: 'admin-leave-request-statuses',
                component: () => import("./views/admin/leave-requests/LeaveRequestStatusesList.vue"),
            },
            {
                path: '/admin/leave-request-types',
                name: 'admin-leave-request-types',
                component: () => import("./views/admin/leave-requests/LeaveRequestTypesList.vue"),
            },
            {
                path: '/admin/notification-types',
                name: 'admin-notification-types',
                component: () => import("./views/admin/notification-types/NotificationTypesList.vue"),
            },
            {
                path: '/admin/petty-cash',
                name: 'admin-petty-cash',
                component: () => import("./views/admin/petty-cash/PettyCashList.vue"),
            },
            {
                path: '/admin/petty-cash-reasons',
                name: 'admin-petty-cash-reasons',
                component: () => import("./views/admin/petty-cash/PettyCashReasonsList.vue"),
            },
            {
                path: '/admin/petty-cash-locations',
                name: 'admin-petty-cash-locations',
                component: () => import("./views/admin/petty-cash/PettyCashLocationsList.vue"),
            },
            {
                path: '/admin/petty-cash-notes',
                name: 'admin-petty-cash-notes',
                component: () => import("./views/admin/petty-cash/PettyCashNotesList.vue"),
            },
            {
                path: '/admin/petty-cash-vendors',
                name: 'admin-petty-cash-vendors',
                component: () => import("./views/admin/petty-cash/PettyCashVendorsList.vue"),
            },
            {
                path: '/admin/pip-reasons',
                name: 'admin-pip-reasons',
                component: () => import("./views/admin/pip-reasons/PipReasonsList.vue"),
            },
            {
                path: '/admin/termination-reasons',
                name: 'admin-termination-reasons',
                component: () => import("./views/admin/termination-reasons/TerminationReasonList.vue"),
            },
            {
                path: '/admin/writeup-reasons',
                name: 'admin-writeup-reasons',
                component: () => import("./views/admin/writeup-reasons/WriteUpReasonList.vue"),
            },
            {
                path: '/admin/writeup-levels',
                name: 'admin-writeup-levels',
                component: () => import("./views/admin/writeup-levels/WriteUpLevelList.vue"),
            },
            {
                path: '/uploads/call-detail-log',
                name: 'uploads-call-detail-log',
                component: () => import("./views/uploads/CallDetailLogUpload.vue"),
            },
            {
                path: '/uploads/retreaver',
                name: 'uploads-retreaver',
                component: () => import("./views/uploads/RetreaverUpload.vue"),
            },
            {
                path: '/uploads/agent-performance',
                name: 'uploads-agent-performance',
                component: () => import("./views/uploads/AgentPerformanceUpload.vue"),
            },
            {
                path: '/reports/agent-hours',
                name: 'reports-agent-hours',
                component: () => import("./views/reports/AgentHours.vue"),
            },
            {
                path: '/reports/call-center-hours',
                name: 'reports-call-center-hours',
                component: () => import("./views/reports/CallCenterHours.vue"),
            },
            {
                path: '/reports/client-hours',
                name: 'reports-client-hours',
                component: () => import("./views/reports/ClientHours.vue"),
            },
            {
                path: '/reports/agent-daily-stats',
                name: 'reports-agent-daily-stats',
                component: () => import("./views/reports/AgentDailyStats.vue"),
            },
            {
                path: '/reports/agent-hours-daily',
                name: 'reports-agent-hours-daily',
                component: () => import("./views/reports/AgentHoursDaily.vue"),
            },
            {
                path: '/reports/attendance',
                name: 'reports-attendance',
                component: () => import("./views/reports/Attendance.vue"),
            },
            {
                path: '/reports/attendance-detail',
                name: 'reports-attendance-detail',
                component: () => import("./views/reports/AttendanceDetail.vue"),
            },
            {
                path: '/reports/dispositions',
                name: 'reports-dispositions',
                component: () => import("./views/reports/Dispositions.vue"),
            },
            {
                path: '/reports/license-swap',
                name: 'reports-license-swap',
                component: () => import("./views/reports/LicenseSwap.vue"),
            },
            {
                path: '/reports/licensing',
                name: 'reports-licensing',
                component: () => import("./views/reports/Licensing.vue"),
            },
            {
                path: '/reports/onscript-upload-logs',
                name: 'reports-onscript-upload-logs',
                component: () => import("./views/reports/OnScriptUploadLogs.vue"),
            },
            {
                path: '/reports/payroll',
                name: 'reports-payroll',
                component: () => import("./views/reports/Payroll.vue"),
            },
            {
                path: '/reports/payroll/exceptions',
                name: 'reports-payroll-exceptions',
                component: () => import("./views/reports/PayrollExceptions.vue"),
            },
            {
                path: '/reports/performance',
                name: 'reports-performance',
                component: () => import("./views/reports/Performance.vue"),
            },
            {
                path: '/reports/performance-tracker',
                name: 'reports-performance-tracker',
                component: () => import("./views/reports/PerformanceTracker.vue"),
            },
            {
                path: '/reports/performance-tracker-overview',
                name: 'reports-performance-tracker-overview',
                component: () => import("./views/reports/PerformanceTrackerOverview.vue"),
            },
			{
                path: '/admin/audit-log',
                name: 'admin-audit-log',
                component: () => import("./views/admin/audit-log/AuditLog.vue"),
            },
        ],
    },
    {
        path: '/',
        component: () => import("./views/UnauthenticatedBase.vue"),
        children: [
            {
                path: 'forgot-password',
                component: () => import("./views/unauthenticated/ForgotPassword.vue"),
                name: 'forgot-password'

            },
            {
                path: 'reset',
                component: () => import("./views/unauthenticated/ResetPassword.vue"),
                name: 'reset-password'

            },
            {
                path: 'login',
                component: () => import("./views/unauthenticated/Login.vue"),
                name: 'login'

            },
            {
                path: 'logout',
                component: () => import("./views/unauthenticated/Logout.vue"),
                name: 'logout'
            },
        ]
    },
];

const router = new createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        return {left: 0, top: 0}
    }
});

export default router;
