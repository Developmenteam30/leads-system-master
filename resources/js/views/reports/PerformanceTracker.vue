<template>
    <h2 v-if="isEvaluation">Evaluation #{{ form.evaluation_id }}</h2>
    <h2 v-else>Performance Tracker</h2>
    <AsyncPage>
        <MDBContainer class="performance-tracker-view mb-5">
            <template v-if="!isEvaluation">
                <DispositionStatus
                    :payload="form"
                />

                <MDBContainer class="p-0 m-0 mt-4">
                    <div class="d-sm-flex flex-row align-items-center">
                        <quick-jump interval="week" @update="setQuickJump" :start-date="form.start_date" :end-date="form.end_date"></quick-jump>
                    </div>
                </MDBContainer>

                <SmartSelectAjax :key="agentKey" class="mt-3" label="Agent" ajax-url="options/dialer_agents/by_date" :ajax-payload="{ ...form }" v-model:selected="form.agent_id"
                                 style="max-width: 500px;"/>

                <MDBBtn color="primary" @click="getReport" :disabled="!isReadyToSubmit" class="mt-3">Search
                    <MDBSpinner tag="span" size="sm" v-if="isLoading" class="ms-2"/>
                </MDBBtn>
            </template>

            <template v-if="dataset && Object.keys(dataset).length && dataset.agent">

                <MDBRow class="mt-3">
                    <MDBCol col="12" md="6" lg="3">
                        <MDBCard text="center" class="bg-opacity-25 bg-info">
                            <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Agent</h5></MDBCardHeader>
                            <MDBCardBody class="p-2">
                                <MDBCardText><strong>{{ displayDashIfBlank(dataset.agent.agent_name) }}</strong></MDBCardText>
                            </MDBCardBody>
                        </MDBCard>
                    </MDBCol>
                    <MDBCol col="12" md="6" lg="3">
                        <MDBCard text="center" class="bg-opacity-25 bg-info mt-3 mt-md-0">
                            <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Team</h5></MDBCardHeader>
                            <MDBCardBody class="p-2">
                                <MDBCardText><strong>{{ displayDashIfBlank(dataset.agent.team_name) }}</strong></MDBCardText>
                            </MDBCardBody>
                        </MDBCard>
                    </MDBCol>
                    <MDBCol col="12" md="6" lg="3" class="mt-3 mt-lg-0">
                        <MDBCard text="center" class="bg-opacity-25 bg-info">
                            <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Manager</h5></MDBCardHeader>
                            <MDBCardBody class="p-2">
                                <MDBCardText><strong>{{ displayDashIfBlank(dataset.agent.manager_name) }}</strong></MDBCardText>
                            </MDBCardBody>
                        </MDBCard>
                    </MDBCol>
                    <MDBCol col="12" md="6" lg="3" class="mt-3 mt-lg-0">
                        <MDBCard text="center" class="bg-opacity-25 bg-info">
                            <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Hire Date</h5></MDBCardHeader>
                            <MDBCardBody class="p-2">
                                <MDBCardText><strong>{{ displayDashIfBlank(formattedHireDate) }}<span v-if="dataset.training"> (OJT)</span></strong></MDBCardText>
                            </MDBCardBody>
                        </MDBCard>
                    </MDBCol>
                </MDBRow>

                <MDBRow class="mt-3" v-if="dataset.pip">
                    <MDBContainer col="12">
                        <MDBAlert color="warning" static><h4><i class="fas fa-exclamation-circle me-3"></i> This agent is in PIP for the current period.</h4></MDBAlert>
                    </MDBContainer>
                </MDBRow>

                <MDBRow>
                    <MDBCol md="6">
                        <MDBRow class="mt-3 mb-0">
                            <h3 class="text-center mb-4 mb-lg-0 performance-period">Current Period ({{ dataset.current_start_date }} - {{ dataset.current_end_date }})</h3>
                        </MDBRow>
                        <template v-if="dataset && dataset.current_average && dataset.current">
                            <performance-tracker-section
                                :averages="dataset.current_average"
                                :values="dataset.current"
                            />
                        </template>
                        <template v-else>
                            <MDBAlert color="danger" static class="mt-3"><h3 class="mb-0">No data was found for this date range.</h3></MDBAlert>
                        </template>
                    </MDBCol>
                    <MDBCol md="6">
                        <MDBRow class="mt-3 mb-0">
                            <h3 class="text-center mb-4 mb-lg-0 performance-period">Previous Period ({{ dataset.previous_start_date }} - {{ dataset.previous_end_date }})</h3>
                        </MDBRow>
                        <template v-if="dataset && dataset.previous_average && dataset.previous">
                            <performance-tracker-section
                                :averages="dataset.previous_average"
                                :values="dataset.previous"
                            />
                        </template>
                        <template v-else>
                            <MDBAlert color="danger" static class="mt-3"><h3 class="mb-0">No data was found for this date range.</h3></MDBAlert>
                        </template>
                    </MDBCol>
                </MDBRow>

                <h5 class="mt-4">Evaluation</h5>
                <template v-if="isEvaluation">
                    <DataField
                        label="Evaluator"
                        :value="evaluationForm.reporter_name"
                    />
                    <DataField
                        label="Evaluation Date"
                        :value="evaluationForm.created_at"
                    />
                    <DataField
                        label="Write-Up Attached"
                        :value="evaluationForm.writeup_flag"
                    />
                </template>
                <MDBTextarea class="mt-4" label="Evaluation Notes" v-model="evaluationForm.notes" :readonly="isEvaluation"/>

                <div class="mt-4"></div>
                <WriteupListAgent
                    :agent-id="form.agent_id"
                    :show-add-button="authStore.hasAccessToArea('ACCESS_AREA_MENU_PEOPLE_WRITEUPS_AGENTS')"
                />

                <MDBBtn v-if="false" color="primary" class="mt-3" @click="showWriteupModal('')">Add a Write-Up</MDBBtn>

                <h5 class="mt-4">Lowest Duration Successful Transfers</h5>
                <CustomDatatableAjax
                    ajax-url="reports/performance-tracker/lowest-transfers"
                    :ajax-payload="{ ...formReactive }"
					:ajax-payload-sync="{ ...form }"
                    :entries="10000"
                    striped
                    fixedHeader
                    class="mt-1"
                    :pagination="false"
                    :auto-height="false"
                />

                <template v-if="!isEvaluation">
                    <h5 class="mt-4">Write-Up</h5>
                    <SmartSelectAjax class="mt-4" label="Write-Up Reason" ajax-url="options/dialer_agent_writeup_reasons" v-model:selected="writeupForm.reason_id" :required="true"/>

                    <SmartSelectAjax class="mt-4" label="Write-Up Level" ajax-url="options/dialer_agent_writeup_levels" v-model:selected="writeupForm.writeup_level_id" :show-required="true"
                                     v-if="writeupForm.reason_id"/>

                    <MDBTextarea class="mt-4 input-required" label="Write-Up Notes" v-model="writeupForm.writeup_notes" v-if="writeupForm.reason_id"/>

                    <MDBBtn color="primary" @click="submitEvaluation" :disabled="!isReadyToSubmit" class="mt-3">Submit Evaluation
                        <MDBSpinner tag="span" size="sm" v-if="isSubmitting" class="ms-2"/>
                    </MDBBtn>
                </template>
            </template>
        </MDBContainer>
    </AsyncPage>
</template>

<script setup>
import {
    MDBAlert,
    MDBBtn,
    MDBCard,
    MDBCardBody,
    MDBCardHeader,
    MDBCardText,
    MDBCol,
    MDBContainer,
    MDBRow,
    MDBSpinner,
    MDBTextarea,
} from "mdb-vue-ui-kit";
import QuickJump from "@/components/QuickJump.vue";
import {ref, computed, watch, nextTick, onMounted} from "vue";
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import apiClient from "@/http";
import {displayDashIfBlank} from "@/helpers";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import {toast} from "vue3-toastify";
import {useRoute} from 'vue-router';
import {clone, cloneDeep, isEqual} from "lodash";
import PerformanceTrackerSection from "@/components/PerformanceTrackerSection.vue";
import DataField from "@/components/DataField.vue";
import DispositionStatus from "@/components/DispositionStatus.vue";
import WriteupListAgent from "@/views/writeups/WriteupListAgent.vue";
import {authStore} from "@/store/auth-store";

const route = useRoute();
const isLoading = ref(false);
const isSubmitting = ref(false);
const dataset = ref({});
const agentKey = ref(0);
const writeupKey = ref(0);
const writeupModal = ref(false);
const writeupModalValues = ref('');

const formReactive = ref({
	search: '',
});

const form = ref({
    evaluation_id: route.query.evaluation_id ? parseInt(route.query.evaluation_id) : '',
    agent_id: route.query.agent_id ? parseInt(route.query.agent_id) : '',
    start_date: route.query.start_date || DateTime.now().startOf("week").toISODate(),
    end_date: route.query.end_date || DateTime.now().endOf("week").toISODate(),
});

const writeupFormDefaults = {
    reason_id: '',
    writeup_level_id: '',
    writeup_notes: '',
};
const writeupForm = ref(clone(writeupFormDefaults));

const evaluationFormDefaults = {
    notes: '',
};
const evaluationForm = ref(clone(evaluationFormDefaults));

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
}

const isReadyToSubmit = computed(() => {
    return false === isLoading.value &&
        form.value.agent_id !== '' &&
        form.value.start_date !== '' &&
        form.value.end_date !== '';
})

const isEvaluation = computed(() => {
    return form.value.evaluation_id !== '';
})

onMounted(() => {
    if (isReadyToSubmit.value === true) {
        getReport();
    }
});

watch(() => cloneDeep(form), (selection, prevSelection) => {
    if (!isEqual(selection.value.start_date, prevSelection.value.start_date) ||
        !isEqual(selection.value.end_date, prevSelection.value.end_date)) {
        dataset.value = {};
        agentKey.value++;
    }
    if (!isEqual(selection.value.agent_id, prevSelection.value.agent_id)) {
        dataset.value = {};
    }
});

const getReport = () => {
    isLoading.value = true;
    writeupKey.value++;
    writeupForm.value = writeupFormDefaults;
    evaluationForm.value = evaluationFormDefaults;

    apiClient.get('reports/performance-tracker', {params: form.value})
        .then(({data}) => {
            dataset.value = data;
            evaluationForm.value = data.evaluation;
        }).catch(error => {
    }).finally(() => {
        isLoading.value = false;
    });
};

const formattedHireDate = computed(() =>
    dataset && dataset.value.agent && dataset.value.agent && dataset.value.agent.effectiveHireDate ? DateTime.fromISO(dataset.value.agent.effectiveHireDate, {setZone: true}).toLocaleString(DateTime.DATE_MED) : ''
);

const showWriteupModal = (id) => {
    writeupModalValues.value = id;
    writeupModal.value = true;
};

const updateWriteupModal = (value) => {
    writeupModal.value = value;
};

const submitEvaluation = () => {
    isSubmitting.value = true;

    apiClient.post(`evaluations/manage`, {...form.value, ...evaluationForm.value, ...writeupForm.value})
        .then(({data}) => {
            writeupForm.value = clone(writeupFormDefaults);
            evaluationForm.value = clone(evaluationFormDefaults);
            toast.success("The evaluation has been submitted.");
        }).catch(error => {
    }).finally(() => {
        isSubmitting.value = false;
    });
}
</script>
<style lang="scss">
</style>
