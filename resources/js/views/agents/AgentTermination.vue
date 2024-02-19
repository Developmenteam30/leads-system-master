<template>
    <AbstractList
        ajax-url="terminations/manage"
        ajax-edit-url-base="terminations/manage"
        title-singular="Termination"
        title-plural="Termination Log"
        :form="form"
        @update:form="(value) => form = value"
        @update:loading="(value) => isLoading = value"
        :has-actions="true"
        :embedded="embedded"
        :defaults="defaults"
        :key="key"
        @reload="key++"
        :show-add-button="true"
        exportable
    >
        <template v-slot:edit-modal>
            <SmartSelectAjax label="Agent" ajax-url="options/dialer_agents" :ajax-payload="{id: form.agent_id}" v-model:selected="form.agent_id" :show-required="true"/>

            <MDBDatepicker v-model="form.sdr_report_date" inputToggle label="SDR Report Date " format="YYYY-MM-DD" disableFuture confirmDateOnSelect :required="true"/>

            <MDBDatepicker v-model="form.pip_issue_date" inputToggle label="PIP Issue Date" format="YYYY-MM-DD" disableFuture confirmDateOnSelect :required="true"/>

            <MDBDatepicker v-model="form.term_approve_date" inputToggle label="Term Approve Date" format="YYYY-MM-DD" disableFuture confirmDateOnSelect :required="false"/>

            <SmartSelectAjax label="Reason" ajax-url="options/termination_reasons" v-model:selected="form.reason_id" :show-required="true"/>

            <MDBTextarea label="Notes" v-model="form.notes" rows="3"/>
        </template>
        <template v-slot:view-modal>
            <DataField
                label="Agent Name"
                v-if="form.agent"
                :value="form.agent.agent_name"
            >
            </DataField>

            <DataField
                label="SDR Report Date"
                v-if="form.sdr_report_date"
                :value="form.sdr_report_date"
            >
            </DataField>

            <DataField
                label="PIP Issue Date"
                v-if="form.pip_issue_date"
                :value="form.pip_issue_date"
            >
            </DataField>

            <DataField
                label="Term Approve Date"
                v-if="form.term_approval_date"
                :value="form.term_approval_date"
            >
            </DataField>

            <DataField
                label="Nominator"
                v-if="form.nominator"
                :value="form.nominator.agent_name"
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
import {MDBDatepicker, MDBTextarea} from "mdb-vue-ui-kit";
import {ref} from "vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import Nl2br from "vue3-nl2br";
import DataField from "@/components/DataField.vue";

const isLoading = ref(false);
const key = ref(0);

const form = ref({
    id: null,
    agent_id: null,
});

const defaults = ref({
    id: null,
    agent_id: null
});

</script>
