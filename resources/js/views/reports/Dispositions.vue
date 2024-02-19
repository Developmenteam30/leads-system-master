<template>
    <h2>Dispositions Report</h2>
    <AsyncPage>
        <MDBContainer fluid class="mt-3 p-0 m-0">
            <FilterRow>
                <quick-jump :constrain="true" interval="week" @update="setQuickJump" :disabled="isLoading"></quick-jump>
            </FilterRow>
            <FilterRow class="mt-2">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>
        <CustomDatatableAjax
            :ajax-url="`reports/dispositions`"
            :ajax-payload="formReactive"
            :ajax-payload-sync="form"
            ref="datatable"
            class="mt-3 datatable-small-font table-right-align"
            :exportable="true"
            @update:loading="(value) => isLoading = value"
        />
    </AsyncPage>
</template>

<script setup>
import {MDBContainer} from "mdb-vue-ui-kit";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import QuickJump from "@/components/QuickJump.vue";
import {ref} from "vue";
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import FilterRow from "@/components/FilterRow.vue";
import RunReport from "@/components/RunReport.vue";

const isLoading = ref(false);
const datatable = ref(null);

const formReactive = ref({
    search: '',
});

const form = ref({
    start_date: DateTime.now().startOf("week").toISODate(),
    end_date: DateTime.now().endOf("week").toISODate(),
});

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
}

const runReport = () => {
    datatable.value.$.exposed.getValues()
}
</script>
