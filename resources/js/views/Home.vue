<template>
    <AsyncPage>
        <template v-if="authStore.hasAccessToArea('ACCESS_AREA_DASHBOARD_REPORT')">
            <ManagementDashboard/>
        </template>
        <template v-else-if="authStore.hasAccessToArea('ACCESS_AREA_MENU_DASHBOARDS_EMPLOYEE')">
            <EmployeeDashboard/>
        </template>
        <template v-else-if="authStore.hasAccessToArea('ACCESS_AREA_MENU_DASHBOARDS_TEAM')">
            <TeamDashboard/>
        </template>
        <template v-else-if="authStore.hasAccessToArea('ACCESS_AREA_MENU_DASHBOARDS_AGENT')">
            <AgentDashboard/>
        </template>
        <template v-else>
            <h2>Dashboard</h2>
            <p>Please select a menu option above</p>
        </template>
    </AsyncPage>
</template>

<script setup>
import {authStore} from "@/store/auth-store.ts";
import TeamDashboard from "@/views/dashboards/TeamDashboard.vue";
import AsyncPage from "@/components/AsyncPage.vue";
import AgentDashboard from "@/views/dashboards/AgentDashboard.vue";
import EmployeeDashboard from "@/views/dashboards/EmployeeDashboard.vue";
import ManagementDashboard from "@/views/dashboards/ManagementDashboard.vue";
</script>

<style scoped lang="scss">
</style>
