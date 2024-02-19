<template>
    <h2>Agent Hours Report</h2>
    <AsyncPage>
        <MDBContainer fluid class="mt-3 p-0 m-0">
            <FilterRow>
                <quick-jump :constrain="true" interval="week" @update="setQuickJump" :disabled="isLoading"></quick-jump>
                <FilterColumn grow="1" max-width="400px" v-if="authStore.hasAccessToArea('ACCESS_AREA_AGENT_HOURS_FILTERS')">
                    <SmartSelectAjax label="Call Center" ajax-url="options/companies" v-model:selected="form.company_ids" multiple :disabled="isLoading"/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="300px" v-if="authStore.hasAccessToArea('ACCESS_AREA_AGENT_HOURS_FILTERS')">
                    <SmartSelectAjax label="Campaign" ajax-url="options/dialer_products" v-model:selected="form.product_id" :disabled="isLoading"/>
                </FilterColumn>
                <FilterColumn v-if="authStore.hasAccessToArea('ACCESS_AREA_AGENT_HOURS_FILTERS') && authStore.hasAccessToArea('ACCESS_AREA_BILLABLE_RATES')">
                    <MDBRadio label="Billable" value="billable" v-model="form.view" inline name="viewOptions" :disabled="isLoading"/>
                    <MDBRadio label="Payable" value="payable" v-model="form.view" inline name="viewOptions" :disabled="isLoading"/>
                </FilterColumn>
            </FilterRow>
            <FilterRow class="mt-2">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>
        <DispositionStatus
            v-if="authStore.hasAccessToArea('ACCESS_AREA_SHOW_DISPOSITION_COUNTS')"
            :payload="formReactive"
            :payloadsync="form"
            ref="dispositionstatusdatatable"
        />
        <MDBAlert color="danger" static v-if="error">{{ error }}</MDBAlert>
        <CustomDatatableAjax
            :ajax-url="`reports/agent-hours`"
            :ajax-payload="formReactive"
            :ajax-payload-sync="form"
            ref="datatable"
            class="mt-3 table-right-align datatable-small-font"
            @update:loading="(value) => isLoading = value"
            :exportable="true"
        />
    </AsyncPage>
</template>

<script setup>
import {MDBAlert, MDBContainer, MDBRadio} from "mdb-vue-ui-kit";
import {authStore} from "@/store/auth-store.ts";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import QuickJump from "@/components/QuickJump.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import {ref} from "vue";
import DispositionStatus from "@/components/DispositionStatus.vue";
import RunReport from "@/components/RunReport.vue";

const datatable = ref(null);
const dispositionstatusdatatable = ref(null);

const error = ref('');
const isLoading = ref(false);

const formReactive = ref({
    search: '',
});

const form = ref({
    start_date: DateTime.now().startOf("week").toISODate(),
    end_date: DateTime.now().endOf("week").toISODate(),
    company_ids: '',
    product_id: '',
    include_qa: 'include',
    view: 'billable',
});

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
};

const runReport = () => {
    datatable.value.$.exposed.getValues()
    dispositionstatusdatatable.value.$.exposed.getValues()
}

</script>

<style scoped lang="scss">
</style>
