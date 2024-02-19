<template>
    <AbstractList
        :ajax-url="`evaluations/manage`"
        title-singular="Agent Evaluation"
        title-plural="Agent Evaluations"
        :form="form"
        :filter-form="filterForm"
        @update:form="(value) => form = value"
        @update:loading="(value) => isLoading = value"
        :has-actions="true"
        :key="key"
        @reload="reloadResults"
        :exportable="true"
        :show-add-button="false"
        :emit-view-action="true"
        @action:view="changeRoute"
    >
        <template v-slot:filters>
            <MDBContainer fluid class="mt-3 p-0 m-0">
                <FilterRow>
                    <quick-jump interval="week" @update="setQuickJump" :start-date="filterForm.start_date" :end-date="filterForm.end_date" :disabled="isLoading"></quick-jump>
                    <FilterColumn>
                        <MDBInput v-model="filterForm.search" :label="`Search by Agent name`" :readonly="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="400px">
                        <SmartSelectAjax label="Call Center" ajax-url="options/companies" v-model:selected="filterForm.company_ids" :disabled="isLoading" multiple/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="Team" ajax-url="options/dialer_teams" v-model:selected="filterForm.team_id" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="Manager" ajax-url="options/dialer_employees" v-model:selected="filterForm.manager_agent_id" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn>
                        <SmartSelect label="Status" v-model:options="statusOptions" :preselect="false" v-model:selected="filterForm.status" clearButton :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" :last="true">
                        <MDBCheckbox label="Include archived" v-model="filterForm.include_archived" :disabled="isLoading"/>
                    </FilterColumn>
                </FilterRow>
            </MDBContainer>
        </template>
        <template v-slot:view-modal>
            <DataField
                label="Agent Name"
                v-if="form.agent && form.agent.agent_name"
                :value="form.agent.agent_name"
            >
            </DataField>

            <DataField
                label="Evaluation Date"
                v-if="form.created_at"
                :value="formattedEvaluationDate(form.created_at)"
            >
            </DataField>
        </template>
    </AbstractList>
</template>

<script setup>
import {
    MDBCheckbox,
    MDBContainer,
    MDBInput,
} from "mdb-vue-ui-kit";
import {ref} from 'vue';
import {value} from "lodash/seq";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import QuickJump from "@/components/QuickJump.vue";
import {DateTime} from "luxon";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import router from "@/routes";
import SmartSelect from "@/components/SmartSelect.vue";
import DataField from "@/components/DataField.vue";
import AbstractList from "@/components/AbstractList.vue";

const showModal = ref(false);
const isLoading = ref(false);
const modalValues = ref({});

const form = ref({
    id: null,
});

const filterForm = ref({
    start_date: DateTime.now().startOf("week").toISODate(),
    end_date: DateTime.now().endOf("week").toISODate(),
    search: '',
    company_ids: '',
    team_id: '',
    manager_agent_id: '',
    status: '',
    actions: 1,
    include_archived: false,
});

const key = ref(0);

const statusOptions = ref([
    {
        value: 'completed',
        text: 'Completed',
    },
    {
        value: 'outstanding',
        text: 'Outstanding',
    },
]);

const changeRoute = (values) => {
    if (values.evaluation_id !== null && values.evaluation_id !== '-') {
        router.push({
            name: 'reports-performance-tracker', query: {
                evaluation_id: values.evaluation_id,
                agent_id: values.agent_id,
                start_date: values.start_date,
                end_date: values.end_date,
            }
        })
    } else {
        router.push({
            name: 'reports-performance-tracker', query: {
                agent_id: values.agent_id,
                start_date: filterForm.value.start_date,
                end_date: filterForm.value.end_date,
            }
        })
    }
};

const setQuickJump = (values) => {
    filterForm.value.start_date = values.startDate;
    filterForm.value.end_date = values.endDate;
}

const reloadResults = () => {
    key.value++;
    emit("reload");
}

const formattedEvaluationDate = (value) => value ? DateTime.fromISO(value, {setZone: true}).toFormat('yyyy-MM-dd') : '';
</script>
<style lang="scss">
</style>
