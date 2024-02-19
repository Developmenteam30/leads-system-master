<template>
    <AbstractList
        :ajax-url="ajaxUrl"
        ajax-edit-url-base="leave-requests"
        title-singular="Leave Request"
        title-plural="Leave Requests"
        :form="form"
        :filter-form="ajaxPayload ? ajaxPayload : filterForm"
        @update:form="(value) => form = value"
        @update:loading="(value) => isLoading = value"
        :has-actions="true"
        :embedded="embedded"
        :defaults="defaults"
        :key="key"
        @reload="reload"
        :show-add-button="showAddButton"
        :exportable="!embedded"
    >
        <template v-slot:filters v-if="showFilters">
            <MDBContainer fluid class="mt-3 p-0 m-0">
                <FilterRow>
                    <quick-jump interval="default" @update="setQuickJump" :disabled="isLoading" :start-date="filterForm.start_date" :end-date="filterForm.end_time"></quick-jump>
                    <FilterColumn>
                        <MDBInput v-model="filterForm.search" :label="`Search by agent name`" :readonly="isLoading"/>
                    </FilterColumn>
                    <FilterColumn>
                        <SmartSelectAjax label="Approval Status" ajax-url="options/leave_request_statuses" v-model:selected="filterForm.leave_request_status_ids" multiple=""/>
                    </FilterColumn>
                </FilterRow>
            </MDBContainer>
        </template>
        <template v-slot:edit-modal>
            <template v-if="authStore.hasAccessToArea('ACCESS_AREA_EDIT_LEAVE_REQUESTS')">
                <SmartSelectAjax label="Agent" ajax-url="options/dialer_agents" :ajax-payload="{id: form.agent_id}" v-model:selected="form.agent_id" :show-required="true"/>
            </template>

            <div v-if="form.id && !embedded" class="mb-4">
                <LeaveRequestListAgent
                    :agent-id="form.agent_id"
                    :show-add-button="false"
                    :key="form.agent_id"
                />
            </div>

            <SmartSelectAjax label="Request Type" ajax-url="options/leave_request_types" v-model:selected="form.leave_request_type_id" :show-required="true"/>

            <template v-if="form.leave_request_type_id">
                <!-- Vacation -->
                <template v-if="4 === form.leave_request_type_id">
                    <p>Please select the start and end date of your vacation request.</p>
                    <MDBDatepicker label="Start Date" v-model="form.formattedStartDate" :max="form.formattedEndDate" inputToggle confirmDateOnSelect format="YYYY-MM-DD" :required="true"/>
                    <MDBDatepicker label="End Date" v-model="form.formattedEndDate" :min="form.formattedStartDate" inputToggle confirmDateOnSelect format="YYYY-MM-DD" :required="true"/>
                </template>

                <!-- PTO -->
                <template v-if="5 === form.leave_request_type_id">
                    <template v-if="form.id || timeAvailable.ptoRemaining > 0">
                        <p>PTO Remaining: <strong>{{ timeAvailable.ptoRemaining }} Hour(s)</strong></p>
                        <p>Please select the start and end date of your PTO request.</p>

                        <MDBRadio
                            tag="span"
                            :btnCheck="true"
                            labelClass="btn btn-primary"
                            label="Full Day"
                            name="ptoType"
                            value="full"
                            v-model="ptoType"
                        />
                        <MDBRadio
                            tag="span"
                            :btnCheck="true"
                            labelClass="btn btn-primary"
                            label="Partial Day"
                            name="ptoType"
                            value="partial"
                            v-model="ptoType"
                        />
                        <div v-if="ptoType === 'partial'" style="padding-top: 15px">
                            <MDBDatepicker label="Start Date" v-model="form.formattedStartDate" inputToggle confirmDateOnSelect format="YYYY-MM-DD" :required="true"/>
                            <MDBSelect v-model:options="startTimeOptions" v-model:selected="form.formattedStartTime" label="Start Time" :required="true"/>
                            <MDBSelect v-model:options="endTimeOptions" v-model:selected="form.formattedEndTime" label="End Time" :required="true"/>
                        </div>
                        <div v-else style="padding-top: 15px">
                            <MDBDatepicker label="Start Date" v-model="form.formattedStartDate" inputToggle confirmDateOnSelect format="YYYY-MM-DD" :required="true"/>
                        </div>

                        <MDBTextarea label="Notes" v-model="form.notes" class="input-required"/>
                    </template>
                    <MDBAlert color="danger" static v-else><i class="fas fa-exclamation-circle me-3"></i> You have no PTO hours available.</MDBAlert>
                </template>

                <!-- Sick -->
                <template v-if="6 === form.leave_request_type_id">
                    <template v-if="form.id || timeAvailable.sickRemaining > 0">
                        <p>Please select the start and end date of your Sick request.</p>
                        <p>Sick Remaining: {{ timeAvailable.sickRemaining }} Day(s)</p>
                        <MDBDatepicker label="Start Date" v-model="form.formattedStartDate" :max="form.formattedEndDate" inputToggle confirmDateOnSelect format="YYYY-MM-DD" :required="true"/>
                        <MDBDatepicker label="End Date" v-model="form.formattedEndDate" :min="form.formattedStartDate" inputToggle confirmDateOnSelect format="YYYY-MM-DD" :required="true"/>

                        <MDBAlert color="warning" static class="mb-4"><i class="fas fa-exclamation-triangle me-3"></i>
                            Please be sure to upload your doctor paperwork in your profile within 24 hours of your absence. Not doing so may lead to disciplinary action and/or termination.
                        </MDBAlert>
                        <MDBTextarea label="Notes" v-model="form.notes" class="input-required"/>
                    </template>
                    <MDBAlert color="danger" static v-else><i class="fas fa-exclamation-circle me-3"></i> You have no sick days available.</MDBAlert>
                </template>

                <template v-if="authStore.hasAccessToArea('ACCESS_AREA_EDIT_LEAVE_REQUESTS')">
                    <template v-if="form.leave_request_type_id !== 6 || (form.leave_request_type_id === 6 && form?.documents?.length > 0)">
                        <SmartSelectAjax label="Approval Status" ajax-url="options/leave_request_statuses" v-model:selected="form.leave_request_status_id" :show-required="true"/>
                    </template>
                    <template v-else>
                        <MDBAlert color="warning" static class="mb-4"><i class="fas fa-exclamation-triangle me-3"></i>
                            Status can't be approved, the agent hasn't uploaded a doctors note yet.
                        </MDBAlert>
                        <MDBSelect
                            v-model:options="approvalOptions"
                            v-model:selected="form.leave_request_status_id"
                        />
                    </template>
                </template>
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
                label="Type"
                v-if="form.type"
                :value="form.type.name"
            >
            </DataField>

            <DataField
                label="Start Date"
                v-if="form.formattedStartDate"
                :value="form.formattedStartDate"
            >
            </DataField>

            <DataField
                label="Time"
                v-if="form.formattedStartTime"
                :value="form.formattedStartTime"
            >
            </DataField>

            <DataField
                label="End Date"
                v-if="form.formattedEndDate"
                :value="form.formattedEndDate"
            >
            </DataField>

            <DataField
                label="Time"
                v-if="form.formattedEndTime"
                :value="form.formattedEndTime"
            >
            </DataField>

            <p class="mb-0"><strong>Notes:</strong></p>
            <nl2br tag="p" v-if="form.notes" :text="form.notes"></nl2br>

            <DataField
                label="Status"
                v-if="form.status"
                :value="form.status.name"
            >
            </DataField>

            <h3 class="mt-3">Documents</h3>
            <CustomDatatableAjax
                :ajax-url="`documents/leave-request/${form.id}`"
                :ajax-payload="{ documentable_type: 'leave_request', documentable_id: form.id}"
                class="mt-0"
                :auto-height="false"
            />
        </template>
        <template v-slot:upload-modal>
            <SmartSelectAjax label="Document Type" ajax-url="options/document-types/leave_request" v-model:selected="form.document_type_id" :show-required="true"/>

            <file-uploader
                :showDateField="false"
                :api-endpoint="`documents/leave-request/${form.id}`"
                :ajax-payload="{ ...form }"
                @uploaded="key++"
                success-message="Your document has been uploaded."
                file-types-message="Accepted file types: Text File, Word Document, Excel Document, PDF, Image File (PNG, JPEG, GIF, BMP, TIFF)"
            ></file-uploader>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBDatepicker, MDBRadio, MDBInput, MDBSelect, MDBTextarea, MDBAlert, MDBContainer} from "mdb-vue-ui-kit";
import {onMounted, ref, watch, watchEffect} from "vue";
import {cloneDeep} from "lodash";
import {authStore} from "@/store/auth-store";
import {DateTime} from "luxon";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import DataField from "@/components/DataField.vue";
import Nl2br from "vue3-nl2br";
import LeaveRequestListAgent from "@/views/leave-requests/LeaveRequestListAgent.vue";
import FilterRow from "@/components/FilterRow.vue";
import QuickJump from "@/components/QuickJump.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FileUploader from "@/components/FileUploader.vue";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import apiClient from "@/http";

const props = defineProps({
    ajaxUrl: {
        type: String,
        required: true,
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
    showFilters: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["reload"]);

const key = ref(0);
const isLoading = ref(false);
const ptoType = ref('full');

const approvalOptions = ref([
    {
        value: 1,
        text: 'Pending',
        isActive: true,
        isArchived: false
    },
    {
        value: 3,
        text: 'Denied',
        isActive: true,
        isArchived: false
    },
]);

const startTimeOptions = ref(Array.from({length: 24 * 4}, (_, i) => {
    const dt = DateTime.fromObject({hour: Math.floor(i / 4), minute: (i % 4) * 15});
    const timeString = dt.toFormat('hh:mm a');
    return {text: timeString, value: timeString};
}));

const endTimeOptions = ref(Array.from({length: 24 * 4}, (_, i) => {
    const dt = DateTime.fromObject({hour: Math.floor(i / 4), minute: (i % 4) * 15});
    const timeString = dt.toFormat('hh:mm a');
    return {text: timeString, value: timeString};
}));

const filterForm = ref({
    actions: 1,
    agent_id: props.agentId,
    start_date: null,
    end_time: null,
    leave_request_status_ids: '1',
});

const form = ref({
    id: null,
    agent_id: authStore.getState().agent.id,
    leave_request_type_id: '',
    leave_request_status_id: 1, // Pending
    time: null,
    notes: null,
    formattedStartTime: '',
    formattedEndTime: '',
});

const timeAvailable = ref({
    vacationAccrued: 0,
    sickAccrued: 0,
    ptoAccrued: 0,
    vacationRemaining: 0,
    sickRemaining: 0,
    ptoRemaining: 0,
    vacationTaken: 0,
    sickTaken: 0,
    ptoTaken: 0,
    vacationPending: 0,
    sickPending: 0,
    ptoPending: 0,
});

const defaults = cloneDeep(form);

const setQuickJump = (values) => {
    filterForm.value.start_date = values.startDate;
    filterForm.value.end_time = values.endDate;
}

const reload = () => {
    key.value++;
    emit('reload');
}

const loadTimeAvailable = () => {
    apiClient.get(`leave-requests/agent/${form.value.agent_id}/time_available`)
        .then(({data}) => {
            timeAvailable.value = data;
        }).catch(error => {
    }).finally(() => {
    });
}

watchEffect(() => {
    form.value.agent_id = props.agentId;
});

watch(() => form.value.agent_id, (value) => {
    loadTimeAvailable();
});

onMounted(() => {
    loadTimeAvailable();
});

watch(() => form.value.formattedStartDate, (value) => {
    form.value.formattedEndDate = value;
});

watch(() => ptoType.value, (value) => {
    if (value === 'full') {
        form.value.formattedStartTime = '09:00 AM';
        form.value.formattedEndTime = '05:00 PM';
    }
});

</script>
<style lang="scss">
</style>
