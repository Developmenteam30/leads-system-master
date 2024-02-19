<template>
    <h2>Performance Tracker</h2>

    <MDBContainer class="p-0 m-0 mt-4">
        <div class="d-sm-flex flex-row align-items-center">
            <quick-jump interval="week" @update="setQuickJump" :start-date="form.start_date" :end-date="form.end_date"></quick-jump>
        </div>
    </MDBContainer>

    <MDBContainer class="performance-tracker-view mb-5">
        <template v-if="!isLoading && dataset && Object.keys(dataset).length">
            <MDBRow>
                <MDBCol md="6">
                    <MDBRow class="mt-3 mb-0">
                        <h3 class="text-center mb-4 mb-lg-0 performance-period">Current Period ({{ dataset.current_start_date }} - {{ dataset.current_end_date }})</h3>
                    </MDBRow>
                    <template v-if="dataset && dataset.current_average && dataset.current">
                        <performance-tracker-section
                            :averages="dataset.current_average"
                            :values="dataset.current"
                            performant="Agent"
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
                            performant="Agent"
                        />
                    </template>
                    <template v-else>
                        <MDBAlert color="danger" static class="mt-3"><h3 class="mb-0">No data was found for this date range.</h3></MDBAlert>
                    </template>
                </MDBCol>
            </MDBRow>
        </template>
        <div v-else class="flex-grow-1 text-center">
            <MDBSpinner/>
        </div>
    </MDBContainer>
</template>

<script setup>
import {
    MDBAlert,
    MDBCol,
    MDBContainer,
    MDBRow, MDBSpinner,
} from "mdb-vue-ui-kit";
import PerformanceTrackerSection from "@/components/PerformanceTrackerSection.vue";
import apiClient from "@/http";
import {onMounted, ref, watch} from "vue";
import {cloneDeep, debounce, isEqual} from "lodash";
import QuickJump from "@/components/QuickJump.vue";
import {DateTime} from "luxon";
import {useRoute} from "vue-router";

const props = defineProps({
    agentId: {
        type: [String, Number],
        required: true
    }
})

const route = useRoute();
const dataset = ref({});
const isLoading = ref(false);

const form = ref({
    agent_id: props.agentId,
    start_date: DateTime.now().startOf("week").toISODate(),
    end_date: DateTime.now().endOf("week").toISODate(),
});


watch(() => cloneDeep(form), (selection, prevSelection) => {
    if (!isEqual(selection, prevSelection)) {
        debouncedGetValues()
    }
});

const getValues = () => {
    if (!form.value.start_date || !form.value.end_date || !form.value.agent_id) {
        return;
    }

    isLoading.value = true;

    apiClient.get('reports/performance-tracker', {params: form.value})
        .then(({data}) => {
            dataset.value = data;
        }).catch(error => {
    }).finally(() => {
        isLoading.value = false;
    });
};

const debouncedGetValues = debounce(getValues, 500);

onMounted(() => {
    getValues();
});

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
}
</script>
