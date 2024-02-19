<template>
    <h2>Licensing Report</h2>
    <AsyncPage>
        <MDBContainer class="mt-3 p-0 m-0">
            <FilterRow>
                <FilterColumn>
                    <MDBInput v-model="formReactive.search" :label="`Search by name`" :readonly="isLoading"/>
                </FilterColumn>
                <FilterColumn>
                    <SmartSelectAjax label="Campaign" :disabled="isLoading" ajax-url="options/dialer_products" v-model:selected="form.product_id"/>
                </FilterColumn>
                <FilterColumn>
                    <MDBCheckbox label="Include inactive" v-model="form.include_inactive" :disabled="isLoading"/>
                </FilterColumn>
            </FilterRow>
            <FilterRow class="mt-2">
                <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
            </FilterRow>
        </MDBContainer>
        <CustomDatatableAjax
            :ajax-url="`reports/licensing`"
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
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import {
    MDBCheckbox,
    MDBContainer,
    MDBInput,
} from "mdb-vue-ui-kit";
import {ref} from 'vue';
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import AsyncPage from "@/components/AsyncPage.vue";
import RunReport from "@/components/RunReport.vue";

const datatable = ref(null);
const isLoading = ref(false);

const formReactive = ref({
    search: '',
});

const form = ref({
    include_inactive: false,
    product_id: '',
});

const runReport = () => {
    datatable.value.$.exposed.getValues()
}


</script>
<style lang="scss">
</style>
