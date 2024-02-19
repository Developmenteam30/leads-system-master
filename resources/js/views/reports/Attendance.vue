<template>
    <h2>Attendance Report</h2>
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
            <FilterRow class="mt-3">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>
        <CustomDatatableAjax
            :ajax-url="`reports/attendance`"
            :ajax-payload="{ ...formReactive }"
            :ajax-payload-sync="{ ...form }"
            ref="datatable"
            class="mt-3 table-center-align datatable-small-font"
            @update:loading="(value) => isLoading = value"
        />
    </AsyncPage>
</template>

<script setup>
import {
    MDBContainer,
} from "mdb-vue-ui-kit";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import apiClient from "../../http";
import SmartSelect from "@/components/SmartSelect.vue";
import {onBeforeMount, ref, watch} from "vue";
import QuickJump from "@/components/QuickJump.vue";
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import RunReport from "@/components/RunReport.vue";

const companyOptions = ref([]);
const isLoading = ref(false);
const datatable = ref(null);

const formReactive = ref({
    search: '',
});

const form = ref({
    company_ids: '',
    start_date: DateTime.now().startOf("month").toISODate(),
    end_date: DateTime.now().endOf("month").toISODate(),
    statuses: '1',
});

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
.attendance-cell {
    display: flex;
    flex-direction: row;
}

.attendance-cell-high {
    background-color: var(--report-green);
}

.attendance-cell-medium {
    background-color: var(--report-orange);
}

.attendance-cell-low {
    background-color: var(--report-red);
}

.attendance-calls,
.attendance-wrapup {
    width: 50%;
    text-align: center;
}

.attendance-calls {
    font-weight: bold;
    font-size: 1.2em;
}
</style>
