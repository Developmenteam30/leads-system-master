<template>
    <AbstractList
        ajax-url="audit-log/manage"
        ajax-edit-url-base="audit-log/manage"
        title-singular="Audit Log"
        title-plural="Audit Log"
        :form="form"
        :filter-form="filterFormReactive"
        :filter-form-sync="filterForm"
        :has-actions="true"
        @update:form="(value) => form = value"
        @update:loading="(value) => isLoading = value"
        :defaults="defaults"
        :show-add-button="false"
        :embedded="false"
        ref="datatable"
        :key="key"
        @reload="key++"
    >
        <template v-slot:filters>
            <MDBContainer class="mt-3 p-0 m-0">
                <FilterRow>
                    <quick-jump interval="week" @update="setQuickJump" :disabled="isLoading"/>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="People" ajax-url="options/dialer_all_people/by_date" :ajax-payload="{ ...filterForm }" v-model:selected="filterForm.agent_ids" multiple/>
                    </FilterColumn>
                    <FilterColumn last>
                        <MDBInput v-model="filterFormReactive.search" :label="`Search by Action`" :readonly="isLoading"/>
                    </FilterColumn>
                </FilterRow>
                <FilterRow class="mt-2">
                    <RunReport @clicked="runReport" :is-loading="isLoading"></RunReport>
                </FilterRow>
            </MDBContainer>
        </template>

        <template v-slot:view-modal>
            <template v-if="form.notes && form.notes.newValues && form.notes.oldValues">
                <p>Legend: Green = Field was added &bull; Orange = Field was changed &bull; Red = Field was deleted</p>
                <MDBTable striped bordered>
                    <thead>
                    <tr>
                        <th scope="col">Field</th>
                        <th scope="col">Old Value</th>
                        <th scope="col">New Value</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="key in [... new Set(Object.keys(form.notes.newValues).concat(Object.keys(form.notes.oldValues)))]" :class="calculateClass(key, form.notes)">
                        <td><strong>{{ key }}</strong></td>
                        <td>{{ form.notes.oldValues[key] ?? '' }}</td>
                        <td>{{ form.notes.newValues[key] ?? '' }}</td>
                    </tr>
                    </tbody>
                </MDBTable>
            </template>
            <template v-else>
                <MDBTable striped bordered>
                    <thead>
                    <tr>
                        <th scope="col">Field</th>
                        <th scope="col">Value</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="key in Object.keys(form.notes)">
                        <td><strong>{{ key }}</strong></td>
                        <td>{{ form.notes[key] }}</td>
                    </tr>
                    </tbody>
                </MDBTable>
            </template>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {
    MDBContainer,
    MDBInput,
    MDBTable,
} from "mdb-vue-ui-kit";
import {computed, ref} from "vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import {DateTime} from "luxon";
import QuickJump from "@/components/QuickJump.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import RunReport from "@/components/RunReport.vue";

const isLoading = ref(false);
const key = ref(0);
const datatable = ref(null);

const form = ref({
    id: null,
});

const defaults = ref({
    id: null,
});

const filterForm = ref({
    start_date: DateTime.now().startOf("week").toISODate(),
    end_date: DateTime.now().endOf("week").toISODate(),
    agent_ids: [],
});

const filterFormReactive = ref({
    search: '',
});

const setQuickJump = (values) => {
    filterForm.value.start_date = values.startDate;
    filterForm.value.end_date = values.endDate;
}

const runReport = () => {
    datatable.value.$.exposed.getValues()
}

const calculateClass = (key, notes) => {
    if (notes.oldValues[key] && notes.newValues[key] && notes.oldValues[key] !== notes.newValues[key]) {
        return 'table-warning';
    } else if (!notes.oldValues[key] && notes.newValues[key]) {
        return 'table-success';
    } else if (!notes.newValues[key] && notes.oldValues[key]) {
        return 'table-danger';
    }
    return '';
};
</script>
<style lang="scss">
</style>
