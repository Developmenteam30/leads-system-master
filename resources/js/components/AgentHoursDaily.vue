<template>
    <h2>{{ title }}</h2>
    <AsyncPage>
        <MDBContainer fluid class="mt-3 p-0 m-0">
            <FilterRow>
                <quick-jump :constrain="true" interval="week" @update="setQuickJump" :start-date="form.start_date" :end-date="form.end_date" :disabled="isLoading"></quick-jump>
                <FilterColumn grow="1" max-width="300px">
                    <MDBInput v-model="formReactive.search" :label="`Search by Name`" :readonly="isLoading"/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="400px" v-if="!isPayrollReport && authStore.hasAccessToArea('ACCESS_AREA_AGENT_HOURS_FILTERS')">
                    <SmartSelectAjax label="Call Center" :disabled="isLoading" ajax-url="options/companies" v-model:selected="form.company_ids" multiple/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="400px" v-if="isPayrollReport && authStore.hasAccessToArea('ACCESS_AREA_AGENT_HOURS_FILTERS')">
                    <SmartSelectAjax label="Call Center" :disabled="isLoading" ajax-url="options/payroll_companies" v-model:selected="form.company_ids" multiple/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="300px" v-if="authStore.hasAccessToArea('ACCESS_AREA_AGENT_HOURS_FILTERS')">
                    <SmartSelectAjax label="Campaign" :disabled="isLoading" ajax-url="options/dialer_products" v-model:selected="form.product_ids" multiple/>
                </FilterColumn>
                <FilterColumn v-if="!isPayrollReport && authStore.hasAccessToArea('ACCESS_AREA_AGENT_HOURS_FILTERS') && authStore.hasAccessToArea('ACCESS_AREA_BILLABLE_RATES')">
                    <MDBRadio label="Billable" value="billable" v-model="form.view" inline name="viewOptions" :disabled="isLoading"/>
                    <MDBRadio label="Payable" value="payable" v-model="form.view" inline name="viewOptions" :disabled="isLoading"/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="300px" v-if="authStore.hasAccessToArea('ACCESS_AREA_AGENT_HOURS_FILTERS')">
                    <SmartSelectAjax label="Agent Types" ajax-url="options/dialer_agent_types" v-model:selected="form.agent_type_ids" multiple :disabled="isLoading"/>
                </FilterColumn>
            </FilterRow>
            <FilterRow class="mt-3">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
                <FilterColumn v-if="authStore.hasAccessToArea('ACCESS_AREA_EDIT_AGENT_HOURS')">
                    <MDBBtn color="primary" @click="toggleAddModal">Add Agent</MDBBtn>
                </FilterColumn>
                <FilterColumn v-if="authStore.hasAccessToArea('ACCESS_AREA_RECALCULATE_PAYROLL')">
                    <MDBBtn color="primary" @click="recalculatePayroll" :disabled="isRecalculating">Recalculate
                        <MDBSpinner tag="span" size="sm" v-if="isRecalculating" class="ms-2"/>
                    </MDBBtn>
                </FilterColumn>
                <FilterColumn v-if="isPayrollReport && authStore.hasAccessToArea('ACCESS_AREA_EMAIL_PAYROLL_REPORT')">
                    <MDBBtn color="primary" @click="sendEmail" :disabled="isSending">Email Payroll Report
                        <MDBSpinner tag="span" size="sm" v-if="isSending" class="ms-2"/>
                    </MDBBtn>
                </FilterColumn>
                <FilterColumn v-if="authStore.hasAccessToArea('ACCESS_AREA_AGENT_HOURS_BULK_EDIT')">
                    <MDBBtn color="primary" @click="showBulkHoursUpdate(null)" :disabled="!selectedRows || selectedRows.length < 2">Bulk Edit Hours</MDBBtn>
                </FilterColumn>
            </FilterRow>
        </MDBContainer>

        <DispositionStatus
            v-if="authStore.hasAccessToArea('ACCESS_AREA_SHOW_DISPOSITION_COUNTS')"
            :payload="formReactive"
            :payloadsync="form"
            ref="dispositionstatusdatatable"
        />

        <PayrollExceptions
            v-if="authStore.hasAccessToArea('ACCESS_AREA_MENU_REPORTS_PAYROLL_EXCEPTIONS')"
            :payload="formReactive"
            :payloadsync="form"
            ref="payrollexceptionsdatatable"
        >
        </PayrollExceptions>

        <HolidayWarnings
            v-if="authStore.hasAccessToArea('ACCESS_AREA_MENU_REPORTS_PAYROLL_EXCEPTIONS')"
            :payload="formReactive"
            :payloadsync="form"
            ref="holidaywarningsdatatable"
        >
        </HolidayWarnings>

        <CustomDatatableAjax
            :ajax-url="isPayrollReport ? `reports/payroll` : `reports/agent-hours-by-day`"
            :ajax-payload="formReactive"
            :ajax-payload-sync="form"
            ref="datatable"
            class="mt-3 table-right-align datatable-small-font"
            :clickableRows="authStore.hasAccessToArea('ACCESS_AREA_EDIT_AGENT_HOURS')"
            @cell-click-values="toggleEditModal"
            :exportable="true"
            :key="payrollKey"
            @update:loading="(value) => isLoading = value"
            @selected-rows="setSelectedRows"
        />
    </AsyncPage>

    <AgentModal
        :showModal="agentModal"
        @update:showModal="(value) => agentModal = value"
        @reload="reloadResults"
        :modalValues="agentModalValues"
        title="Agent"
    >
    </AgentModal>
    <AgentHoursModal
        :showModal="editHoursModal"
        @update:showModal="(value) => editHoursModal = value"
        @reload="reloadResults"
        :modal-values="modalValues"
        :form="form"
        :weekdays="weekdays"
        :is-payroll-report="isPayrollReport"
    >
    </AgentHoursModal>
    <AgentHoursBulkModal
        :showModal="editHoursBulkModal"
        @update:showModal="(value) => editHoursBulkModal = value"
        @reload="reloadResults"
        :rowIds="selectedRows"
        :form="form"
        :weekdays="weekdays"
        :is-payroll-report="isPayrollReport"
        :key="payrollKey"
    >
    </AgentHoursBulkModal>

</template>

<script setup>
import {
    MDBBtn,
    MDBContainer,
    MDBInput,
    MDBRadio,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import apiClient from "../http";
import {computed, ref} from "vue";
import {
    authStore,
} from "@/store/auth-store.ts";
import QuickJump from "./QuickJump.vue";
import SmartSelectAjax from "./SmartSelectAjax.vue";
import CustomDatatableAjax from "./CustomDatatableAjax.vue";
import FilterColumn from "./FilterColumn.vue";
import FilterRow from "./FilterRow.vue";
import {toast} from 'vue3-toastify';
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import AgentModal from "@/views/agents/AgentModal.vue";
import PayrollExceptions from "@/components/PayrollExceptions.vue";
import HolidayWarnings from "@/components/HolidayWarnings.vue";
import DispositionStatus from "@/components/DispositionStatus.vue";
import AgentHoursBulkModal from "@/views/agents/AgentHoursBulkModal.vue";
import {useRoute} from "vue-router";
import AgentHoursModal from "@/views/agents/AgentHoursModal.vue";
import RunReport from "@/components/RunReport.vue";

const props = defineProps({
    title: String,
});

const selectedRows = ref([]);
const weekdays = {
    mon: 'Mon',
    tue: 'Tue',
    wed: 'Wed',
    thu: 'Thu',
    fri: 'Fri',
    sat: 'Sat',
    sun: 'Sun',
};

const route = useRoute();
const addHoursModal = ref(false);
const editHoursModal = ref(false);
const editHoursBulkModal = ref(false);
const datatable = ref(null);
const dispositionstatusdatatable = ref(null);
const payrollexceptionsdatatable = ref(null);
const holidaywarningsdatatable = ref(null);

const formReactive = ref({
    search: '',
});

const form = ref({
    start_date: route.query.start_date || DateTime.now().startOf("week").toISODate(),
    end_date: route.query.end_date || DateTime.now().endOf("week").toISODate(),
    company_ids: '',
    product_ids: '',
    view: 'billable',
    agent_type_ids: '',
});
const isLoading = ref(false);
const isSaving = ref(false);
const isSending = ref(false);
const isRecalculating = ref(false);
const modalValues = ref({});
const agentOptions = ref([]);
const agentModal = ref(false);
const agentModalValues = ref({});
const payrollKey = ref(0);
const toggleAddModal = index => {
    getOptionsAgents();
    modalValues.value = {
        agent_id: null,
        mon: '',
        tue: '',
        wed: '',
        thu: '',
        fri: '',
        sat: '',
        sun: '',
    };
    addHoursModal.value = true;
};

const toggleEditModal = (values, colIndex) => {
    // Edit the agent if the first column is clicked
    if (colIndex === 0) {
        if (authStore.hasAccessToArea('ACCESS_AREA_ADD_EDIT_AGENTS')) {
            agentModalValues.value = values.agent;
            agentModal.value = true;
        }
    } else {
        if (authStore.hasAccessToArea('ACCESS_AREA_EDIT_AGENT_HOURS')) {
            modalValues.value = values;
            editHoursModal.value = true;
        }
    }
};

const isPayrollReport = computed(() => {
    return 'Weekly Payroll Report' === props.title;
})

const sendEmail = () => {
    isSending.value = true;

    apiClient.post(`reports/payroll/email`, {
        ...form.value,
    })
        .then(({data}) => {
            toast.success("The payroll spreadsheet is being generated.");
        }).catch((error) => {
    }).finally(() => {
        isSending.value = false;
    });
}

const recalculatePayroll = () => {
    isRecalculating.value = true;

    apiClient.post(`reports/payroll/recalculate`, {
        ...form.value,
    })
        .then(({data}) => {
            toast.success("Payroll numbers are being recalculated.");
        }).catch(error => {
    }).finally(() => {
        isRecalculating.value = false;
    });

}

const getOptionsAgents = () => {
    apiClient.get('options/dialer_agents')
        .then(({data}) => {
            agentOptions.value = data;
        });
}

const showBulkHoursUpdate = (values) => {
    if (!authStore.hasAccessToArea('ACCESS_AREA_ADD_EDIT_AGENTS')) {
        return;
    }

    editHoursBulkModal.value = true;
}

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
}

const reloadResults = () => {
    payrollKey.value++;
    selectedRows.value = [];
}

const runReport = () => {
    datatable.value.$.exposed.getValues()
    dispositionstatusdatatable.value.$.exposed.getValues()
    payrollexceptionsdatatable.value.$.exposed.getValues()
    holidaywarningsdatatable.value.$.exposed.getValues()
}

const setSelectedRows = (value) => {
    selectedRows.value = [];
    if (value && value.length) {
        value.forEach(row => {
            selectedRows.value.push(row.agent_id + '-' + row.internal_campaign_id);
        });
    }
}

</script>
<style lang="scss">
</style>
