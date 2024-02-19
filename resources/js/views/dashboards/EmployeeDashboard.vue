<template>
    <AsyncPage>
        <MDBContainer class="performance-tracker-view mb-5">
            <h1 class="mb-2 mb-4">Employee Dashboard</h1>
            <SmartSelectAjax v-if="authStore.hasAccessToArea('ACCESS_AREA_ADD_EDIT_EMPLOYEES')" label="Employees" ajax-url="options/dialer_employees" v-model:selected="form.agent_id"
                             :show-required="true"/>

            <div class="my-5"></div>

            <MDBTabs v-model="activeTab" v-if="form.agent_id">
                <!-- Tabs navs -->
                <MDBTabNav pills justify tabsClasses="mb-3 text-center flex-md-row flex-column">
                    <MDBTabItem tag="button" :wrap="false" tabId="profile" class="flex-fill">Profile</MDBTabItem>
                    <MDBTabItem tag="button" :wrap="false" tabId="leave-requests" class="flex-fill">Leave Requests</MDBTabItem>
                    <MDBTabItem tag="button" :wrap="false" tabId="write-ups" class="flex-fill">Write-Ups</MDBTabItem>
                    <MDBTabItem tag="button" :wrap="false" tabId="evaluations" class="flex-fill">Evaluations</MDBTabItem>
                    <MDBTabItem tag="button" :wrap="false" tabId="documents" class="flex-fill">Documents</MDBTabItem>
                </MDBTabNav>
                <!-- Tabs navs -->

                <!-- Tabs content -->
                <MDBTabContent>
                    <MDBTabPane tabId="profile">
                        <DashboardProfile
                            type="Employee"
                            :agent="agent"
                            :key="form.agent_id"
                            v-if="agent"
                            @pending:click="() => activeTab = 'leave-requests'"
                        >
                        </DashboardProfile>
                    </MDBTabPane>

                    <MDBTabPane tabId="leave-requests">
                        <LeaveRequestListAgent
                            :agent-id="form.agent_id"
                            :key="form.agent_id"
                        >
                        </LeaveRequestListAgent>
                    </MDBTabPane>

                    <MDBTabPane tabId="write-ups">
                        <WriteupListAgent
                            :agent-id="form.agent_id"
                            :key="form.agent_id"
                            :show-add-button="authStore.hasAccessToArea('ACCESS_AREA_MENU_PEOPLE_WRITEUPS_AGENTS')"
                        />
                    </MDBTabPane>

                    <MDBTabPane tabId="evaluations">
                        <h2 class="mb-3">Evaluations</h2>
                        <EvaluationsDatatable :ajax-url="`evaluations/agent/${form.agent_id}`" :key="form.agent_id"/>
                    </MDBTabPane>

                    <MDBTabPane tabId="documents">
                        <AgentDocumentList
                            :agent-id="form.agent_id"
                            :key="form.agent_id"
                        ></AgentDocumentList>
                    </MDBTabPane>
                </MDBTabContent>
                <!-- Tabs content -->
            </MDBTabs>
        </MDBContainer>
    </AsyncPage>
</template>

<script setup>
import {
    MDBContainer,
    MDBTabContent,
    MDBTabPane,
    MDBTabs,
    MDBTabItem,
    MDBTabNav, MDBCardBody, MDBCard, MDBCardText, MDBRow, MDBCol, MDBCardHeader,
} from "mdb-vue-ui-kit";
import AsyncPage from '@/components/AsyncPage.vue';
import {authStore} from "@/store/auth-store";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import {ref, onMounted, watch} from 'vue';
import PerformanceTrackerAgent from '@/views/reports/PerformanceTrackerAgent.vue';
import EvaluationsDatatable from "@/components/EvaluationsDatatable.vue";
import WriteupListAgent from "@/views/writeups/WriteupListAgent.vue";
import apiClient from "@/http";
import {cloneDeep, isEqual} from "lodash";
import AgentDocumentList from "@/views/documents/AgentDocumentList.vue";
import LeaveRequestListAgent from "@/views/leave-requests/LeaveRequestListAgent.vue";
import DashboardProfile from "./DashboardProfile.vue";

const activeTab = ref('profile');
const agent = ref({});
const isLoading = ref(false);

const form = ref({
    agent_id: authStore.getState().agent.id,
});

onMounted(() => {
    getValues();
})

watch(() => cloneDeep(form.value), (selection, prevSelection) => {
    getValues();
});

const getValues = () => {
    if (form.value.agent_id) {
        isLoading.value = true;
        apiClient.get(`/agent/${form.value.agent_id}`)
            .then(({data}) => {
                agent.value = data;
            }).finally(() => {
            isLoading.value = false;
        });
    }
};
</script>
