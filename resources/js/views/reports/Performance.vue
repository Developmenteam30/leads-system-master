<template>
    <h2>Performance Report</h2>
    <AsyncPage>
        <MDBContainer fluid class="mt-3 p-0 m-0">
            <FilterRow>
                <quick-jump :constrain="true" interval="month" @update="setQuickJump" :disabled="isLoading"></quick-jump>
                <FilterColumn>
                    <SmartSelect label="Call Center" v-model:options="companyOptions" filter clearButton :preselect="false" v-model:selected="form.company_ids" class="company-selector" multiple
                                 :disabled="isLoading"/>
                </FilterColumn>
                <FilterColumn :last="true">
                    <SmartSelectAjax label="Status" :disabled="isLoading" ajax-url="options/dialer_agent_statuses" v-model:selected="form.statuses" multiple/>
                </FilterColumn>
            </FilterRow>
            <FilterRow class="mt-2">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>
        <p class="mt-3">Legend: Transfers &bull; Calls &bull; Transfer Percentage &bull; Wrapup Time</p>
        <CustomDatatableAjax
            :ajax-url="`reports/performance`"
            :ajax-payload-sync="{ ...form }"
            ref="datatable"
            class="mt-3 table-center-align datatable-small-font"
            @cell-click-values="toggleDailyDetailsModal"
            @update:loading="(value) => isLoading = value"
        />
    </AsyncPage>
    <MDBModal
        id="dailyDetailsModal"
        tabindex="-1"
        labelledby="dailyDetailsModalLabel"
        v-model="dailyDetailsModal"
        class="modal-input-spacing"
        staticBackdrop
        size="xl"
        scrollable
    >
        <MDBModalHeader>
            <MDBModalTitle id="dailyDetailsModalLabel">{{ modalTitle }}</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <div class="d-flex justify-content-center">
                <MDBSpinner v-if="isModalLoading"/>
            </div>
            <CustomDatatable
                :dataset="datasetModal"
                v-if="!isModalLoading && datasetModal.rows && datasetModal.rows.length"
                :loading="isModalLoading"
                :pagination="false"
                :entries="100000"
                striped
                fixedHeader
                class="mt-3 table-center-align table-center-align-all datatable-no-pagination datatable-small-font"
            />
            <MDBAlert color="danger" static class="mt-2" v-if="modalError">{{ modalError }}</MDBAlert>
        </MDBModalBody>
        <MDBModalFooter>
            <MDBBtn outline="dark" @click="dailyDetailsModal = false">Close</MDBBtn>
        </MDBModalFooter>
    </MDBModal>

</template>

<script setup>
import {
    MDBAlert,
    MDBBtn,
    MDBContainer,
    MDBModal,
    MDBModalBody,
    MDBModalFooter,
    MDBModalHeader,
    MDBModalTitle,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import CustomDatatable from "@/components/CustomDatatable.vue";
import apiClient from "../../http";
import SmartSelect from "@/components/SmartSelect.vue";
import {onBeforeMount, ref} from "vue";
import QuickJump from "@/components/QuickJump.vue";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import RunReport from "@/components/RunReport.vue";

const datatable = ref(null);
const companyOptions = ref([]);
const datasetModal = ref({
        columns: [],
        rows: [],
    }
);
const dailyDetailsModal = ref(false);
const form = ref({
    company_ids: '',
    start_date: DateTime.now().startOf("month").toISODate(),
    end_date: DateTime.now().endOf("month").toISODate(),
    statuses: '1',
});
const isLoading = ref(false);
const modalError = ref('');
const modalTitle = ref('Details');
const isModalLoading = ref(false);
const toggleDailyDetailsModal = (values, colIndex) => {
    if (colIndex > 0) {
        dailyDetailsModal.value = true;
        let startDate = DateTime.fromISO(form.value.start_date);

        if (values.agent_id) {
            isModalLoading.value = true;
            modalError.value = '';

            const payload = {
                agent_id: values.agent_id,
                date: colIndex > 1 ? startDate.set({day: colIndex - 1}).toISODate() : startDate.toFormat('yyyy-MM'),
            };

            modalTitle.value = 'Details - ' + values.agent_name + ' ' + payload.date;

            apiClient.get('reports/performance/details', {params: payload})
                .then(({data}) => {
                    datasetModal.value.columns = data.columns;
                    datasetModal.value.rows = data.rows;
                }).catch(error => {
                if (error.response && error.response.data && error.response.data.message) {
                    modalError.value = error.response.data.message;
                } else {
                    modalError.value = error;
                }
            }).finally(() => {
                isModalLoading.value = false;
            });
        }
    }
};

onBeforeMount(() => {
    getOptionsCompanies();
})

const getOptionsCompanies = () => {
    apiClient.get('options/companies')
        .then(({data}) => {
            companyOptions.value = data;
        });
}

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
}

const runReport = () => {
    datatable.value.$.exposed.getValues()
}

</script>
<style lang="scss">
.performance-cell {
    display: flex;
    flex-direction: row;
    align-items: center;
}

.performance-transfers {
    font-weight: bold;
    font-size: 1.2em;
    margin-right: 0.25em;
}

.performance-calls {
    margin-right: 0.25em;
}

.performance-percentage {
    padding: 0.5em;
    margin-right: 0.25em;
}

.performance-transfers,
.performance-calls,
.performance-percentage,
.performance-wrapup {
    width: 25%;
    text-align: center;
}

.performance-percentage-high {
    background-color: var(--report-green);
}

.performance-percentage-medium {
    background-color: var(--report-orange);
}

.performance-percentage-low {
    background-color: var(--report-red);
}
</style>
