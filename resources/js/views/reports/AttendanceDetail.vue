<template>
    <h2>Attendance Detail Report</h2>
    <AsyncPage>
        <MDBContainer class="mt-3 p-0 m-0">
            <FilterRow>
                <FilterColumn>
                    <MDBDatepicker v-model="form.date" inline inputToggle label="Date" disableFuture format="YYYY-MM-DD" confirmDateOnSelect :disabled="isLoading"/>
                </FilterColumn>
                <FilterColumn>
                    <MDBInput v-model="formReactive.search" :label="`Search by Agent name`" :readonly="isLoading"/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="300px" :last="true">
                    <SmartSelectAjax label="Status" :disabled="isLoading" ajax-url="options/dialer_attendance_statuses" v-model:selected="form.statuses" multiple/>
                </FilterColumn>
            </FilterRow>
            <FilterRow class="mt-2">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>
        <div style="max-width: 700px">
            <CustomDatatableAjax
                :ajax-url="`reports/attendance-detail`"
                :ajax-payload="formReactive"
                :ajax-payload-sync="form"
                ref="datatable"
                class="mt-3 table-center-align datatable-small-font"
                @update:loading="(value) => isLoading = value"
                :exportable="true"
                :show-count="true"
            />
        </div>
    </AsyncPage>
</template>

<script setup>
import {
    MDBContainer, MDBDatepicker, MDBInput,
} from "mdb-vue-ui-kit";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import {ref} from "vue";
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import FilterRow from "@/components/FilterRow.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import RunReport from "@/components/RunReport.vue";

const datatable = ref(null);

const formReactive = ref({
    search: '',
});

const form = ref({
    // If it's a Saturday or Sunday, default to the previous Friday.
    date: DateTime.now().minus({days: DateTime.now().weekday > 5 ? DateTime.now().weekday - 5 : 0}).toISODate(),
    statuses: '1,2,3,4',
});

const isLoading = ref(false);

const runReport = () => {
    datatable.value.$.exposed.getValues()
}

</script>
<style lang="scss">
</style>
