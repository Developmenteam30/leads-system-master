<template>
    <AbstractList
        :ajax-url="ajaxUrl"
        ajax-edit-url-base="writeups/manage"
        title-singular="Write-Up"
        :title-plural="`${label} Write-Ups`"
        :form="form"
        :filter-form="ajaxPayload ? ajaxPayload : filterForm"
        @update:form="(value) => form = value"
        @update:loading="(value) => isLoading = value"
        :has-actions="true"
        :embedded="embedded"
        :defaults="defaults"
        :key="key"
        @reload="key++"
        :show-add-button="showAddButton"
        :exportable="!embedded"
    >
        <template v-slot:filters v-if="showFilters">
            <MDBContainer fluid class="mt-3 p-0 m-0">
                <FilterRow>
                    <quick-jump interval="week" @update="setQuickJump" :disabled="isLoading"></quick-jump>
                    <FilterColumn :last="'Agent' !== label">
                        <MDBInput v-model="filterForm.search" :label="`Search by ${label} name`" :readonly="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="400px" v-if="'Agent' === label">
                        <SmartSelectAjax label="Call Center" ajax-url="options/companies" v-model:selected="filterForm.company_ids" :disabled="isLoading" multiple/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="300px" v-if="'Agent' === label">
                        <SmartSelectAjax label="Team" ajax-url="options/dialer_teams" v-model:selected="filterForm.team_id" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="300px" v-if="'Agent' === label" :last="'agents' === label">
                        <SmartSelectAjax label="Manager" ajax-url="options/dialer_employees" v-model:selected="filterForm.manager_agent_id" :disabled="isLoading"/>
                    </FilterColumn>
                </FilterRow>
            </MDBContainer>
        </template>
        <template v-slot:edit-modal>
            <SmartSelectAjax v-if="!props.agentId" :label="label" :ajax-url="`options/dialer_${agentType}`" v-model:selected="form.agent_id" :show-required="true"/>

            <template v-if="form.agent_id || props.agentId">
                <div v-if="!form.id && !embedded" class="mb-4">
                    <WriteupListAgent
                        :agent-id="form.agent_id"
                        :show-add-button="false"
                        :key="form.agent_id"
                    />
                </div>

                <MDBDatepicker v-model="form.date" inputToggle label="Incident Date" format="YYYY-MM-DD" disableFuture confirmDateOnSelect :required="true"/>

                <SmartSelectAjax label="Level" ajax-url="options/dialer_agent_writeup_levels" v-model:selected="form.writeup_level_id" :show-required="true"/>

                <SmartSelectAjax label="Reason" ajax-url="options/dialer_agent_writeup_reasons" v-model:selected="form.reason_id" :show-required="true"/>

                <MDBTextarea label="Notes" v-model="form.notes" class="input-required"/>
            </template>
        </template>
        <template v-slot:view-modal>
            <DataField
                label="Agent Name"
                v-if="form.agent"
                :value="form.agent.agent_name"
            >
            </DataField>

            <DataField
                label="Incident Date"
                v-if="form.date"
                :value="form.date"
            >
            </DataField>

            <DataField
                label="Reporter Name"
                v-if="form.reporter"
                :value="form.reporter.agent_name"
            >
            </DataField>

            <DataField
                label="Level"
                v-if="form.level"
                :value="form.level.name"
            >
            </DataField>

            <DataField
                label="Reason"
                v-if="form.reason"
                :value="form.reason.reason"
            >
            </DataField>

            <p class="mb-0"><strong>Notes:</strong></p>
            <nl2br tag="p" v-if="form.notes" :text="form.notes"></nl2br>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBContainer, MDBDatepicker, MDBInput, MDBTextarea} from "mdb-vue-ui-kit";
import {ref} from "vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import Nl2br from "vue3-nl2br";
import DataField from "@/components/DataField.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";
import QuickJump from "@/components/QuickJump.vue";
import {DateTime} from "luxon";
import WriteupListAgent from "@/views/writeups/WriteupListAgent.vue";

const props = defineProps({
    ajaxUrl: {
        type: String,
        required: true,
    },
    ajaxPayload: {
        type: Object,
        required: false,
    },
    agentType: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
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
    showFilters: {
        type: Boolean,
        default: true,
    },
});

const isLoading = ref(false);
const key = ref(0);

const form = ref({
    id: null,
});

const defaults = ref({
    id: null,
    agent_id: props.agentId,
});

const filterForm = ref({
    start_date: props.agentId ? '' : DateTime.now().startOf("week").toISODate(),
    end_date: props.agentId ? '' : DateTime.now().endOf("week").toISODate(),
    search: '',
    company_ids: '',
    team_id: '',
    manager_agent_id: '',
    actions: 1,
    agent_type: props.agentType,
});

const setQuickJump = (values) => {
    filterForm.value.start_date = values.startDate;
    filterForm.value.end_date = values.endDate;
}
</script>
