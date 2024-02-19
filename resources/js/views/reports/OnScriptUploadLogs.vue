<template>
    <h2>OnScript Upload Logs</h2>
    <AsyncPage>
        <MDBContainer class="mt-3 p-0 m-0">
            <FilterRow>
                <quick-jump interval="default" @update="setQuickJump" :disabled="isLoading" :start-date="form.start_date" :end-date="form.end_date"></quick-jump>
                <FilterColumn grow="1" max-width="400px">
                    <SmartSelect label="Status" :disabled="isLoading" v-model:selected="form.statuses" multiple :options="statusOptions"/>
                </FilterColumn>
            </FilterRow>
            <FilterRow class="mt-2">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>
        <p class="my-3">A status of "processing" is normal and means that OnScript accepted the record.</p>
        <CustomDatatableAjax
            :ajax-url="`reports/onscript-upload-logs`"
            :ajax-payload="{ ...formReactive }"
            :ajax-payload-sync="{ ...form }"
            ref="datatable"
            class="mt-3"
            @update:loading="(value) => isLoading = value"
            :exportable="true"
            :show-count="true"
        />
    </AsyncPage>
</template>

<script setup>
import {
    MDBContainer,
} from "mdb-vue-ui-kit";
import {ref} from 'vue';
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import AsyncPage from "@/components/AsyncPage.vue";
import QuickJump from "@/components/QuickJump.vue";
import {DateTime} from "luxon";
import SmartSelect from "@/components/SmartSelect.vue";
import RunReport from "@/components/RunReport.vue";

const datatable = ref(null);
const isLoading = ref(false);

const formReactive = ref({
    search: '',
});

const form = ref({
    start_date: DateTime.now().minus({days: 1}).toISODate(),
    end_date: DateTime.now().minus({days: 1}).toISODate(),
    statuses: '0,1',
});

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
}

const statusOptions = ref([
    {
        value: 1,
        text: 'Accepted',
    },
    {
        value: 0,
        text: 'Rejected',
    },
]);

const runReport = () => {
    datatable.value.$.exposed.getValues()
}

</script>
<style lang="scss">
</style>
