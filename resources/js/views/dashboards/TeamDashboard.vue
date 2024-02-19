<template>
    <AsyncPage>
        <MDBContainer class="performance-tracker-view mb-5">
            <h1>Team Dashboard</h1>
            <MDBContainer class="p-0 m-0 mt-4">
                <div class="d-sm-flex flex-row align-items-center">
                    <quick-jump interval="week" @update="setQuickJump" :start-date="form.start_date" :end-date="form.end_date"></quick-jump>
                </div>
            </MDBContainer>

            <SmartSelectAjax class="mt-3" label="Team" ajax-url="options/dialer_teams" :ajax-payload="form" v-model:selected="form.team_id" style="max-width: 500px;"/>

            <div v-if="isReadyToSubmit" class="mt-4">
                <PerformanceTrackerTeam :ajax-payload="form"/>

                <div class="mt-4"></div>
                <WriteupList
                    label="Agent"
                    agent-type="agents"
                    :key="form.team_id"
                    :ajax-url="`writeups/team/${form.team_id}`"
                    :ajax-payload="form"
                    :embedded="true"
                    :show-add-button="authStore.hasAccessToArea('ACCESS_AREA_MENU_PEOPLE_WRITEUPS_AGENTS')"
                />
            </div>
        </MDBContainer>
    </AsyncPage>
</template>
<script setup>
import {
    MDBContainer,
} from "mdb-vue-ui-kit";
import {ref, computed} from 'vue';
import {useRoute} from 'vue-router';
import {DateTime} from "luxon";
import PerformanceTrackerTeam from '@/views/reports/PerformanceTrackerTeam.vue';
import AsyncPage from '@/components/AsyncPage.vue';
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import QuickJump from "@/components/QuickJump.vue";
import {authStore} from "@/store/auth-store";
import WriteupList from "@/views/writeups/WriteupList.vue";

const route = useRoute();

const form = ref({
    start_date: route.query.start_date || DateTime.now().startOf("week").toISODate(),
    end_date: route.query.end_date || DateTime.now().endOf("week").toISODate(),
    team_id: authStore.getState().agent.team_id || '',
    actions: 1,
});

const isReadyToSubmit = computed(() => {
    return form.value.team_id !== '' &&
        form.value.start_date !== '' &&
        form.value.end_date !== '';
})

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
}
</script>
