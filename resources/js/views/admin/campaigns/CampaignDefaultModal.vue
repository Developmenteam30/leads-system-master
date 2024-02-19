<template>
    <MDBModal
        id="defaultModal"
        tabindex="-1"
        labelledby="defaultModalLabel"
        v-model="showModalLocal"
        class="modal-input-spacing"
        staticBackdrop
    >
        <MDBModalHeader>
            <MDBModalTitle id="defaultModalLabel">{{ modalTitle }}</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <Suspense>
                <template #default>
                    <div>
                        <SmartSelectAjax label="Call Center" ajax-url="options/companies" v-model:selected="modalValuesLocal.company_id" :show-required="true"/>

                        <SmartSelectAjax label="Payment Type" ajax-url="options/dialer_payment_types" v-model:selected="modalValuesLocal.payment_type_id" :show-required="true"/>

                        <MDBInput v-if="authStore.hasAccessToArea('ACCESS_AREA_BILLABLE_RATES')" label="Billable Rate (US$)" v-model="modalValuesLocal.billable_rate"/>

                        <MDBInput label="Payable Rate (US$)" v-model="modalValuesLocal.payable_rate"/>

                        <MDBInput label="Bonus Rate (US$)" v-model="modalValuesLocal.bonus_rate"/>
                    </div>
                </template>
                <template #fallback>
                    <div class="flex-grow-1 text-center">
                        <MDBSpinner/>
                    </div>
                </template>
            </Suspense>
        </MDBModalBody>
        <MDBModalFooter>
            <MDBBtn outline="dark" @click="cancel">Cancel</MDBBtn>
            <MDBBtn color="primary" @click="save">Save changes
                <MDBSpinner tag="span" size="sm" v-if="isSaving" class="ms-2"/>
            </MDBBtn>
        </MDBModalFooter>
    </MDBModal>
</template>

<script setup>
import {useModelWrapper} from "@/modelWrapper";
import {
    MDBAlert,
    MDBBtn,
    MDBCheckbox,
    MDBDatepicker,
    MDBInput,
    MDBModal,
    MDBModalBody,
    MDBModalFooter,
    MDBModalHeader,
    MDBModalTitle,
    MDBSelect,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import apiClient from "@/http";
import {ref, onMounted, watch, computed} from 'vue';
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import {authStore} from "@/store/auth-store";


const props = defineProps({
    title: String,
    showModal: Boolean,
    modalValues: Object,
});

const emit = defineEmits([
    'update:showModal',
    'update:modalValues',
    'reload',
]);

const isSaving = ref(false);
const isPopulating = ref(false);

const modalTitle = computed(() => {
    return (props.modalValues.id ? 'Edit' : 'Add') + ' Call Center Default Values';
})

const save = () => {
    isSaving.value = true;

    if (modalValuesLocal.value.id) {
        apiClient.patch(`campaigns/companies/${modalValuesLocal.value.id}`, modalValuesLocal.value)
            .then(({data}) => {
                showModalLocal.value = false;
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    } else {
        apiClient.post(`campaigns/companies`, modalValuesLocal.value)
            .then(({data}) => {
                showModalLocal.value = false;
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    }
}

const cancel = () => {
    showModalLocal.value = false;
    emit("reload");
}

const showModalLocal = useModelWrapper(props, emit, 'showModal');
const modalValuesLocal = useModelWrapper(props, emit, 'modalValues');
</script>
<style lang="scss">
</style>
