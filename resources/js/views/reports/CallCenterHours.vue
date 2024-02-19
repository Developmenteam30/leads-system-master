<template>
    <h2>Call Center Hours</h2>
    <AsyncPage>
        <MDBContainer fluid class="mt-3 p-0 m-0">
            <FilterRow>
                <quick-jump :constrain="true" interval="week" @update="setQuickJump" :disabled="isLoading"/>
                <FilterColumn grow="1" max-width="300px" :last="!authStore.hasAccessToArea('ACCESS_AREA_UNRESTRICTED_CALL_CENTER_REPORTS')">
                    <MDBInput v-model="formReactive.search" :label="`Search by Name`" :readonly="isLoading"/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="400px" v-if="authStore.hasAccessToArea('ACCESS_AREA_UNRESTRICTED_CALL_CENTER_REPORTS')"
                              :last="authStore.hasAccessToArea('ACCESS_AREA_UNRESTRICTED_CALL_CENTER_REPORTS')">
                    <SmartSelectAjax label="Call Center" :disabled="isLoading" ajax-url="options/companies" v-model:selected="form.company_ids" multiple/>
                </FilterColumn>
            </FilterRow>
            <FilterRow class="mt-2">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>
        <CustomDatatableAjax
            :ajax-url="`reports/call-center-hours`"
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
import {
    MDBContainer,
    MDBInput,
} from "mdb-vue-ui-kit";
import {ref} from "vue";
import QuickJump from "@/components/QuickJump.vue";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import {DateTime} from "luxon";
import AsyncPage from "@/components/AsyncPage.vue";
import {authStore} from "@/store/auth-store";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import RunReport from "@/components/RunReport.vue";

const datatable = ref(null);

const formReactive = ref({
    search: '',
});

const form = ref({
    start_date: DateTime.now().startOf("week").toISODate(),
    end_date: DateTime.now().endOf("week").toISODate(),
});
const isLoading = ref(false);

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
