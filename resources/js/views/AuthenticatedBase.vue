<template>
    <MDBNavbar expand="lg" dark bg="primary" container="fluid" position="sticky" class="mb-4">
        <MDBNavbarBrand>
            <router-link :to="{name:'dashboard'}">
                <img
                    src="/storage/images/logo-menu.svg"
                    height="30"
                    alt="Dashboard"
                />
            </router-link>
        </MDBNavbarBrand>
        <MDBNavbarToggler
            target="#navbarNav"
            @click="mobileCollapse = !mobileCollapse"
        ></MDBNavbarToggler>
        <MDBCollapse v-model="mobileCollapse" id="navbarNav">
            <MDBNavbarNav collapse="navbarNav">
                <NavMenuItem
                    v-for="menuItem in menuItems"
                    :menu-item="menuItem"
                ></NavMenuItem>
            </MDBNavbarNav>
        </MDBCollapse>
        <div class="d-flex w-auto" v-if="mobileCollapse">
            <router-link :to="{ name: 'logout'}" class="logout-link">Logout</router-link>
        </div>
    </MDBNavbar>
    <MDBContainer fluid>
        <router-view :key="$route.fullPath"></router-view>
    </MDBContainer>
</template>

<script setup>
import {authStore} from "@/store/auth-store";
import {
    MDBContainer,
    MDBCollapse,
    MDBNavbar,
    MDBNavbarBrand,
    MDBNavbarNav,
    MDBNavbarToggler,
} from "mdb-vue-ui-kit";
import {ref} from "vue";
import {onBeforeRouteUpdate} from 'vue-router'
import NavMenuItem from "@/views/navigation/NavMenuItem.vue";

onBeforeRouteUpdate((to, from, next) => {
    if (!authStore.isLoggedIn()) {
        next({name: 'login'});
    } else {
        next();
    }
});

const mobileCollapse = ref(false);

const menuItems = ref([
    {
        title: 'Dashboards',
        children: [
            {
                access_area: 'ACCESS_AREA_MENU_DASHBOARDS_AGENT',
                route_name: 'dashboards-agent',
                title: 'Agent',
            },
            {
                access_area: 'ACCESS_AREA_MENU_DASHBOARDS_EMPLOYEE',
                route_name: 'dashboards-employee',
                title: 'Employee',
            },
            {
                access_area: 'ACCESS_AREA_DASHBOARD_REPORT',
                route_name: 'dashboards-management',
                title: 'Management',
            },
            {
                access_area: 'ACCESS_AREA_MENU_DASHBOARDS_TEAM',
                route_name: 'dashboards-team',
                title: 'Team',
            },
        ],
    },
    {
        title: 'Reports',
        children: [
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_AGENT_DAILY_STATS',
                route_name: 'reports-agent-daily-stats',
                title: 'Agent Daily Stats',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_AGENT_HOURS',
                route_name: 'reports-agent-hours',
                title: 'Agent Hours',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_AGENT_HOURS_BY_DAY',
                route_name: 'reports-agent-hours-daily',
                title: 'Agent Hours by Day',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_ATTENDANCE',
                route_name: 'reports-attendance',
                title: 'Attendance',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_ATTENDANCE_DETAIL',
                route_name: 'reports-attendance-detail',
                title: 'Attendance Detail',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_CALL_CENTER_HOURS',
                route_name: 'reports-call-center-hours',
                title: 'Call Center Hours',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_CLIENT_HOURS',
                route_name: 'reports-client-hours',
                title: 'Client Hours',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_DISPOSITIONS',
                route_name: 'reports-dispositions',
                title: 'Dispositions',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_LICENSE_SWAP',
                route_name: 'reports-license-swap',
                title: 'License Swap',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_LICENSING',
                route_name: 'reports-licensing',
                title: 'Licensing',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_ONSCRIPT_UPLOAD_LOGS',
                route_name: 'reports-onscript-upload-logs',
                title: 'OnScript Upload Logs',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_PAYROLL',
                route_name: 'reports-payroll',
                title: 'Payroll',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_PAYROLL_EXCEPTIONS',
                route_name: 'reports-payroll-exceptions',
                title: 'Payroll Exceptions',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_PERFORMANCE',
                route_name: 'reports-performance',
                title: 'Performance',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_PERFORMANCE_TRACKER',
                route_name: 'reports-performance-tracker',
                title: 'Performance Tracker',
            },
            {
                access_area: 'ACCESS_AREA_MENU_REPORTS_PERFORMANCE_TRACKER_OVERVIEW',
                route_name: 'reports-performance-tracker-overview',
                title: 'Performance Tracker Overview',
            },
        ],
    },
    {
        title: 'Uploads',
        children: [
            {
                access_area: 'ACCESS_AREA_MENU_UPLOADS',
                route_name: 'uploads-agent-performance',
                title: 'Agent Hours',
            },
            {
                access_area: 'ACCESS_AREA_MENU_UPLOADS',
                route_name: 'uploads-call-detail-log',
                title: 'Dispositions',
            },
            {
                access_area: 'ACCESS_AREA_MENU_UPLOADS',
                route_name: 'uploads-retreaver',
                title: 'Retreaver',

            },
        ]
    },
    {
        title: 'People',
        children: [
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_AGENTS',
                route_name: 'agents-manage',
                title: 'Agents',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_DOCUMENTS',
                route_name: 'people-documents',
                title: 'Documents',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_EOD_REPORTS',
                route_name: 'people-eod-reports',
                title: 'End of Day Reports',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_EMPLOYEES',
                route_name: 'employees-manage',
                title: 'Employees',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_EVALUATIONS',
                route_name: 'evaluations-manage',
                title: 'Evaluations',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS',
                route_name: 'people-leave-requests',
                title: 'Leave Requests',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_PIPS',
                route_name: 'pips-manage',
                title: 'Performance Improvement Plans',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_TEAMS',
                route_name: 'teams-manage',
                title: 'Teams',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_TERMINATION_LOG',
                route_name: 'termination-log',
                title: 'Termination Log',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_USERS',
                route_name: 'users-manage',
                title: 'Users',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_WRITEUPS_AGENTS',
                route_name: 'writeups-manage-agents',
                title: 'Write-Ups (Agents)',
            },
            {
                access_area: 'ACCESS_AREA_MENU_PEOPLE_WRITEUPS_EMPLOYEES',
                route_name: 'writeups-manage-employees',
                title: 'Write-Ups (Employees)',
            },
        ],
    },
    {
        title: 'Admin',
        children: [
            {
                access_area: 'ACCESS_AREA_MENU_ADMIN_AUDIT_LOG',
                route_name: 'admin-audit-log',
                title: 'Audit Log',
            },
            {
                title: 'Access',
                children: [
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_ACCESS_AREAS',
                        route_name: 'admin-access-area',
                        title: 'Access Areas',
                    },
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_ROLES',
                        route_name: 'admin-roles',
                        title: 'Access Roles',
                    },
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_NOTIFICATION_TYPES',
                        route_name: 'admin-notification-types',
                        title: 'Notification Types',
                    },
                ],
            },
            {
                title: 'Campaigns',
                children: [
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_EXTERNAL_CAMPAIGNS',
                        route_name: 'admin-external-campaigns',
                        title: 'External Campaigns',
                    },
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_CAMPAIGNS',
                        route_name: 'admin-internal-campaigns',
                        title: 'Internal Campaigns',
                    },
                ],
            },
            {
                access_area: 'ACCESS_AREA_MENU_ADMIN_DOCUMENT_TYPES',
                route_name: 'admin-document-types',
                title: 'Document Types',
            },
            {
                access_area: 'ACCESS_AREA_MENU_ADMIN_HOLIDAYS',
                route_name: 'admin-holidays',
                title: 'Holidays',
            },
            {
                access_area: 'ACCESS_AREA_MENU_ADMIN_HOLIDAYS',
                route_name: 'admin-holiday-lists',
                title: 'Holiday Lists',
            },
            {
                title: 'Leave Requests',
                children: [
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_LEAVE_REQUEST_STATUSES',
                        route_name: 'admin-leave-request-statuses',
                        title: 'Leave Request Statuses',
                    },
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_LEAVE_REQUEST_TYPES',
                        route_name: 'admin-leave-request-types',
                        title: 'Leave Request Types',
                    },
                ],
            },
            {
                access_area: 'ACCESS_AREA_MENU_ADMIN_JOB_QUEUE',
                route_name: 'admin-job-queue',
                title: 'Job Queue',
            },
            {
                title: 'Petty Cash',
                children: [
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_PETTY_CASH',
                        route_name: 'admin-petty-cash',
                        title: 'Petty Cash',
                    },
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_PETTY_CASH',
                        route_name: 'admin-petty-cash-locations',
                        title: 'Petty Cash Locations',
                    },
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_PETTY_CASH',
                        route_name: 'admin-petty-cash-notes',
                        title: 'Petty Cash Notes',
                    },
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_PETTY_CASH',
                        route_name: 'admin-petty-cash-reasons',
                        title: 'Petty Cash Reasons',
                    },
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_PETTY_CASH',
                        route_name: 'admin-petty-cash-vendors',
                        title: 'Petty Cash Vendors',
                    },
                ],
            },
            {
                access_area: 'ACCESS_AREA_MENU_ADMIN_PIP_REASONS',
                route_name: 'admin-pip-reasons',
                title: 'PIP Reasons',
            },
            {
                access_area: 'ACCESS_AREA_MENU_ADMIN_TERMINATION_REASONS',
                route_name: 'admin-termination-reasons',
                title: 'Termination Reasons',
            },
            {
                title: 'Write-Ups',
                children: [
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_WRITEUP_LEVELS',
                        route_name: 'admin-writeup-levels',
                        title: 'Write-Up Levels',
                    },
                    {
                        access_area: 'ACCESS_AREA_MENU_ADMIN_WRITEUP_REASONS',
                        route_name: 'admin-writeup-reasons',
                        title: 'Write-Up Reasons',
                    },
                ],
            },
        ],
    },
]);
</script>

<style scoped lang="scss">
.logout-link {
    color: rgba(255, 255, 255, 0.75) !important;
}
</style>
