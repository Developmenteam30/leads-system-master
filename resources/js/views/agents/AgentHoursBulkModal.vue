<template>
    <MDBModal
        id="editHoursModal"
        tabindex="-1"
        labelledby="editHoursModalLabel"
        v-model="showModalLocal"
        staticBackdrop
        size="xl"
    >
        <MDBModalHeader>
            <MDBModalTitle id="editHoursModalLabel">Bulk Edit Hours</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <MDBAlert color="info" static class="mb-4"><i class="fas fa-info-circle me-3"></i> A blank value will make no changes. Use a zero to set a zero value.</MDBAlert>
            <template v-for="(label, key, index) in weekdays">
                <MDBRow class="align-items-center">
                    <MDBCol col="3" lg="2" xl="1">
                        <h4>{{ label }}</h4>
                    </MDBCol>
                    <MDBCol col="6" lg="4" xl="2">
                        <SmartSelect label="Operation" :key="`edit_type_${key}`" v-model:options="editOptions" v-model:selected="modalValues[`${key}_edit_type`]"
                                     @update:selected="(value) => enableEdit(key, value)"/>
                    </MDBCol>
                    <MDBCol col="6" lg="4" xl="2">
                        <MDBInput label="Regular Hours" v-model="modalValues[`${key}_editable_hours`]" :disabled="modalValues[`${key}_disabled`]"/>
                    </MDBCol>
                    <MDBCol col="6" lg="4" xl="2" v-if="formLocal.view === 'payable'">
                        <MDBInput label="Huddle Minutes" v-model="modalValues[`${key}_huddle_minutes`]" :disabled="modalValues[`${key}_disabled`]"/>
                    </MDBCol>
                    <MDBCol col="6" lg="4" xl="2">
                        <MDBInput label="Coaching Minutes" v-model="modalValues[`${key}_coaching_minutes`]" :disabled="modalValues[`${key}_disabled`]"/>
                    </MDBCol>
                </MDBRow>
                <hr v-if="index !== Object.keys(weekdays).length - 1"/>
            </template>
        </MDBModalBody>
        <MDBModalFooter>
            <MDBBtn outline="dark" @click="showModalLocal = false">Cancel</MDBBtn>
            <MDBBtn color="primary" @click="saveHours">Save changes
                <MDBSpinner tag="span" size="sm" v-if="isSaving" class="ml-2"/>
            </MDBBtn>
        </MDBModalFooter>
    </MDBModal>
</template>

<script setup>
import {ref} from "vue";
import {useModelWrapper} from "@/modelWrapper";
import {
    MDBAlert,
    MDBBtn,
    MDBCol,
    MDBInput,
    MDBModal,
    MDBModalBody,
    MDBModalFooter,
    MDBModalHeader,
    MDBModalTitle,
    MDBRow,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import apiClient from "../../http";
import SmartSelect from "@/components/SmartSelect.vue";

const emit = defineEmits([
    'update:showModal',
    'reload',
]);

const props = defineProps({
    showModal: Boolean,
    rowIds: Object,
    form: Object,
    weekdays: Object,
    isPayrollReport: Boolean,
});

const isSaving = ref(false);
const modalValues = ref({
    mon_disabled: true,
    mon_editable_hours: '',
    mon_break_minutes: '',
    mon_huddle_minutes: '',
    mon_coaching_minutes: '',
    mon_edit_type: '',
    tue_disabled: true,
    tue_editable_hours: '',
    tue_break_minutes: '',
    tue_huddle_minutes: '',
    tue_coaching_minutes: '',
    tue_edit_type: '',
    wed_disabled: true,
    wed_editable_hours: '',
    wed_break_minutes: '',
    wed_huddle_minutes: '',
    wed_coaching_minutes: '',
    wed_edit_type: '',
    thu_disabled: true,
    thu_editable_hours: '',
    thu_break_minutes: '',
    thu_huddle_minutes: '',
    thu_coaching_minutes: '',
    thu_edit_type: '',
    fri_disabled: true,
    fri_editable_hours: '',
    fri_break_minutes: '',
    fri_huddle_minutes: '',
    fri_coaching_minutes: '',
    fri_edit_type: '',
    sat_disabled: true,
    sat_editable_hours: '',
    sat_break_minutes: '',
    sat_huddle_minutes: '',
    sat_coaching_minutes: '',
    sat_edit_type: '',
    sun_disabled: true,
    sun_editable_hours: '',
    sun_break_minutes: '',
    sun_huddle_minutes: '',
    sun_coaching_minutes: '',
    sun_edit_type: '',
});

const editOptions = [
    {
        value: '',
        text: 'No Change',
    },
    {
        value: 'set',
        text: 'Set Time',
    },
    {
        value: 'add',
        text: 'Add Time',
    }
]

const enableEdit = (key, value) => {
    modalValues.value[`${key}_disabled`] = (value === '');
};

const saveHours = () => {
    isSaving.value = true;

    apiClient.patch(`reports/payroll/manage/bulk-update`, {
        row_ids: props.rowIds,
        ...formLocal.value,
        ...modalValues.value,
    })
        .then(({data}) => {
            showModalLocal.value = false;
            emit('reload');
        }).catch(error => {
    }).finally(() => {
        isSaving.value = false;
    });
}

const showModalLocal = useModelWrapper(props, emit, 'showModal');
const formLocal = useModelWrapper(props, emit, 'form');
</script>
