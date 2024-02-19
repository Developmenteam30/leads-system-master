<template>
    <h2>Payroll Exceptions</h2>
    <AsyncPage>
        <MDBContainer fluid class="mt-3 p-0 m-0">
            <FilterRow>
                <quick-jump :constrain="true" interval="week" @update="setQuickJump" :disabled="isLoading" :start-date="form.start_date" :end-date="form.end_date"/>
            </FilterRow>
            <FilterRow class="mt-2">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>
        <router-link v-if="returnRoute" :to="{name: returnRoute, query: form}">
            <MDBBtn class="mt-2" color="primary">Back to Previous Report</MDBBtn>
        </router-link>
        <CustomDatatableAjax
            :ajax-url="`reports/payroll/exceptions`"
            :ajax-payload-sync="{ ...form }"
            ref="datatable"
            class="mt-3 datatable-small-font"
            :key="reportKey"
            @update:loading="(value) => isLoading = value"
            :clickableRows="authStore.hasAccessToArea('ACCESS_AREA_ADD_EDIT_AGENTS')"
            @cell-click-values="toggleEditModal"
            :exportable="true"
        />
    </AsyncPage>
    <AgentModal
        :showModal="agentModal"
        @update:showModal="updateAgentModal"
        @reload="reloadResults"
        :modalValues="agentModalValues"
        title="Agent"
    >
    </AgentModal>
</template>

<script setup>
import {
    MDBBtn,
    MDBContainer,
} from "mdb-vue-ui-kit";
import {onMounted, ref} from "vue";
import QuickJump from "@/components/QuickJump.vue";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import FilterRow from "@/components/FilterRow.vue";
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import AgentModal from "@/views/agents/AgentModal.vue";
import {authStore} from "@/store/auth-store";
import {useRoute} from "vue-router";
import RunReport from "@/components/RunReport.vue";

const datatable = ref(null);
const route = useRoute();
const agentModal = ref(false);
const agentModalValues = ref({});
const reportKey = ref(0);
const returnRoute = ref('');

const toggleEditModal = (values, colIndex) => {
    if (!authStore.hasAccessToArea("ACCESS_AREA_ADD_EDIT_AGENTS")) {
        return;
    }

    agentModalValues.value = values.agent;
    agentModal.value = true;
};

const updateAgentModal = (value) => {
    agentModal.value = value;
}

const reloadResults = () => {
    reportKey.value++;
}

const form = ref({
    start_date: route.query.start_date || DateTime.now().startOf("week").toISODate(),
    end_date: route.query.end_date || DateTime.now().endOf("week").toISODate(),
});
const isLoading = ref(false);

onMounted(() => {
    if (route.query.return_route) {
        returnRoute.value = route.query.return_route;
    }
});

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
}

const runReport = () => {
    datatable.value.$.exposed.getValues()
}

</script>
<style lang="scss">
</style>
