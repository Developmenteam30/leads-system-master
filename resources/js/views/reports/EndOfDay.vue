<template>
    <AbstractList
        ajax-url="reports/endofday/manage"
        title-singular="End of Day Report"
        title-plural="End of Day Reports"
        :form="form"
        :filter-form="filterForm"
        :defaults="defaults"
        @update:form="(value) => form = value"
        @update:loading="(value) => isLoading = value"
    >
        <template v-slot:filters>
            <MDBContainer class="mt-3 p-0 m-0">
                <FilterRow>
                    <quick-jump interval="default" @update="setQuickJump" :start-date="filterForm.start_date" :end-date="filterForm.end_date" :disabled="isLoading"></quick-jump>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="Team" ajax-url="options/dialer_teams" v-model:selected="filterForm.team_id" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="Manager" ajax-url="options/dialer_agents" v-model:selected="filterForm.manager_agent_id" :disabled="isLoading"/>
                    </FilterColumn>
                </FilterRow>
            </MDBContainer>
        </template>
        <template v-slot:edit-modal>
            <SmartSelectAjax label="Manager" :disabled="isLoading" ajax-url="options/dialer_employees" v-model:selected="form.manager_agent_id" :required="true" :show-required="true"/>
            <SmartSelectAjax label="Team" :disabled="isLoading" ajax-url="options/dialer_teams" v-model:selected="form.team_id" :required="true" :show-required="true"/>
            <MDBInput label="Team Count" v-model="form.team_count" type="number" :required="true"/>
            <MDBInput label="Head Count" v-model="form.head_count" type="number" :required="true"/>
            <MDBTextarea label="Attendance Notes" v-model="form.attendance_notes" class="input-required"/>
            <MDBTextarea label="Early Leave" v-model="form.early_leave" class="input-required"/>
            <MDBInput label="Day Prior Auto Fail" v-model="form.day_prior_auto_fail" type="number" :required="true"/>
            <MDBInput label="Day Prior Calls Under 89%" v-model="form.day_prior_calls_under_89pct" type="number" :required="true"/>
            <MDBInput label="Completed Evaluations" v-model="form.completed_evaluations" type="number" :required="true"/>
            <MDBInput label="Agents Coached" v-model="form.agents_coached" type="number" :required="true"/>
            <MDBInput label="Agents on PIP" v-model="form.agents_on_pip" type="number" :required="true"/>
            <MDBTextarea label="Notes" v-model="form.notes" class="input-required"/>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBContainer, MDBInput, MDBTextarea} from "mdb-vue-ui-kit";
import {ref} from "vue";
import {DateTime} from "luxon";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import QuickJump from "@/components/QuickJump.vue";
import {authStore} from "@/store/auth-store";
import {cloneDeep} from "lodash";

const isLoading = ref(false);
const form = ref({
    id: null,
    manager_agent_id: authStore.getState().agent.id,
    team_id: null,
    team_count: null,
    head_count: null,
    attendance_notes: null,
    early_leave: null,
    day_prior_auto_fail: null,
    day_prior_calls_under_89pct: null,
    completed_evaluations: null,
    agents_coached: null,
    agents_on_pip: null,
    notes: null,
});

const defaults = cloneDeep(form);

const filterForm = ref({
    manager_agent_id: null,
    team_id: null,
    start_date: DateTime.now().startOf("week").toISODate(),
    end_date: DateTime.now().endOf("week").toISODate(),
});

const setQuickJump = (values) => {
    filterForm.value.start_date = values.startDate;
    filterForm.value.end_date = values.endDate;
}
</script>
