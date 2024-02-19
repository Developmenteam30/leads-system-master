<template>
    <AbstractList
        :ajax-url="ajaxUrl"
        title-singular="Performance Improvement Plan"
        title-plural="Performance Improvement Plans"
        :form="form"
        :filter-form="filterForm"
        :has-actions="true"
        :embedded="embedded"
        @update:form="(value) => form = value"
        :show-add-button="showAddButton"
        :exportable="!embedded"
        :key="key"
    >
        <template v-slot:filters v-if="!embedded">
            <MDBContainer fluid class="mt-3 p-0 m-0">
                <FilterRow>
                    <quick-jump interval="week" @update="setQuickJump" :disabled="isLoading"></quick-jump>
                    <FilterColumn>
                        <MDBInput v-model="filterForm.search" :label="`Search by Agent name`" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="400px">
                        <SmartSelectAjax label="Call Center" ajax-url="options/companies" v-model:selected="filterForm.company_ids" multiple :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="Team" ajax-url="options/dialer_teams" v-model:selected="filterForm.team_ids" multiple :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="Manager" ajax-url="options/dialer_employees" v-model:selected="filterForm.manager_agent_ids" multiple="" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn>
                        <MDBCheckbox label="Include resolved" v-model="filterForm.include_resolved" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn :last="true">
                        <MDBCheckbox label="Include deleted" v-model="filterForm.include_archived" :disabled="isLoading"/>
                    </FilterColumn>
                </FilterRow>
            </MDBContainer>
        </template>
        <template v-slot:edit-modal>
            <SmartSelectAjax label="Agent" ajax-url="options/dialer_agents" :ajax-payload="{id: form.agent_id}" v-model:selected="form.agent_id" :show-required="true"/>

            <MDBDatepicker v-model="form.start_date" inputToggle label="Start Date" format="YYYY-MM-DD" confirmDateOnSelect :required="true"/>

            <SmartSelectAjax label="Reason(s)" ajax-url="options/pip_reasons" v-model:selected="form.reason_ids" :show-required="true" multiple/>

            <SmartSelectAjax label="PIP Resolution" ajax-url="options/pip_resolutions" v-model:selected="form.resolution_id"/>

            <template v-if="form.resolution_id === 2">
                <!--
                <MDBDatepicker v-model="form.termination_date" inputToggle label="Termination Date" format="YYYY-MM-DD" confirmDateOnSelect/>
                -->

                <SmartSelectAjax
                    label="Termination Reason"
                    ajax-url="options/termination_reasons"
                    v-model:selected="form.termination_reason_id"
                    :show-required="true"/>
            </template>
        </template>
        <template v-slot:view-modal>
            <div class="data-card">
                <DataField
                    v-if="form && form.agent"
                    label="Agent Name"
                    :value="form.agent.agent_name"
                >
                </DataField>

                <DataField
                    v-if="form && form.start_date"
                    label="Start Date"
                    :value="form.start_date"
                >
                </DataField>

                <DataField
                    v-if="form && form.reasons_string"
                    label="Reason(s)"
                    :value="form.reasons_string"
                >
                </DataField>

                <DataField
                    v-if="form && form.resolution"
                    label="Resolution"
                    :value="form.resolution.resolution"
                >
                </DataField>
            </div>
        </template>
        <template v-slot:history-modal>
            <PipList
                :agent-id="form.id"
                :embedded="true"
                :show-add-button="false"
                :key="form.id"
                :exportable="false"
            >
            </PipList>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBBtn, MDBCheckbox, MDBContainer, MDBDatepicker, MDBInput, MDBTextarea} from "mdb-vue-ui-kit";
import {ref} from "vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import DataField from "@/components/DataField.vue";
import QuickJump from "@/components/QuickJump.vue";
import FilterRow from "@/components/FilterRow.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import {DateTime} from "luxon";

const props = defineProps({
    ajaxUrl: {
        type: [String, undefined],
        default: 'pips/manage',
    },
    ajaxPayload: {
        type: Object,
        required: false,
    },
    agentId: {
        type: [String, Number, null],
        default: '',
    },
    embedded: {
        type: Boolean,
        default: false,
    },
    showAddButton: {
        type: Boolean,
        default: true,
    },
});

const form = ref({
    id: null,
    agent_id: null,
    resolution_id: null,
    termination_date: null,
    termination_reason_id: null,
});

const key = ref(0);
const isLoading = ref(false);

const filterForm = ref({
    start_date: DateTime.now().startOf("week").toISODate(),
    end_date: DateTime.now().endOf("week").toISODate(),
    search: '',
    company_ids: [],
    team_ids: [],
    manager_agent_ids: [],
    actions: 1,
    agent_id: props.agentId,
    embedded: props.embedded,
    include_resolved: false,
    include_archived: false,
});

const setQuickJump = (values) => {
    filterForm.value.start_date = values.startDate;
    filterForm.value.end_date = values.endDate;
}
</script>
<style lang="scss">
</style>
