<template>
    <h2>Performance Tracker Overview</h2>
    <AsyncPage>
        <MDBContainer fluid class="mt-3 p-0 m-0">
            <FilterRow>
                <quick-jump interval="week" @update="setQuickJump" :disabled="isLoading"></quick-jump>
                <FilterColumn grow="1" max-width="300px">
                    <MDBInput v-model="formReactive.search" :label="`Search by Name`" :readonly="isLoading"/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="400px">
                    <SmartSelectAjax label="Call Center" :disabled="isLoading" ajax-url="options/companies" v-model:selected="form.company_ids" multiple/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="300px">
                    <SmartSelectAjax label="Campaign" :disabled="isLoading" ajax-url="options/dialer_products" v-model:selected="form.product_id"/>
                </FilterColumn>
                <FilterColumn :last="true">
                    <SmartSelectAjax label="Status" :disabled="isLoading" ajax-url="options/dialer_agent_statuses" v-model:selected="form.statuses" multiple/>
                </FilterColumn>
            </FilterRow>
            <FilterRow class="mt-2">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>

        <CustomDatatableAjax
            :ajax-url="`reports/performance-tracker-overview`"
            :ajax-payload="{ ...formReactive }"
            :ajax-payload-sync="{ ...form }"
            ref="datatable"
            class="mt-3 table-right-align datatable-small-font"
            @update:loading="(value) => isLoading = value"
            :exportable="true"
        />
    </AsyncPage>
</template>

<script setup>
import {
    MDBContainer,
    MDBInput,
} from "mdb-vue-ui-kit";
import QuickJump from "@/components/QuickJump.vue";
import {ref} from "vue";
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import RunReport from "@/components/RunReport.vue";

const datatable = ref(null);
const isLoading = ref(false);

const formReactive = ref({
    search: '',
});

const form = ref({
    start_date: DateTime.now().startOf("week").toISODate(),
    end_date: DateTime.now().endOf("week").toISODate(),
    company_ids: '',
    product_id: '',
    statuses: '1',
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
