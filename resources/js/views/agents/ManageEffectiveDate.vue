<template>
    <MDBModal
        id="effectiveDateModal"
        tabindex="-1"
        labelledby="effectiveDateModalLabel"
        v-model="showModalLocal"
        class="modal-input-spacing"
        staticBackdrop
    >
        <MDBModalHeader>
            <MDBModalTitle id="effectiveDateModalLabel">{{ modalTitle }}</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <Suspense>
                <template #default>
                    <div>
                        <SmartSelectAjax :label="`${title} Type`" :ajax-payload="{title: $props.title}" ajax-url="options/dialer_agent_types" v-model:selected="modalValuesLocal.agent_type_id"
                                         :show-required="true"/>

                        <SmartSelectAjax label="Campaign" ajax-url="options/dialer_products" v-model:selected="modalValuesLocal.product_id" @update:selected="campaignSwitched = true"
                                         :show-required="true"/>

                        <MDBAlert v-if="campaignSwitched" color="warning" static class="mb-4"><i class="fas fa-exclamation-triangle me-3"></i> When changing campaigns, please review the rates below or
                            use the "Populate Defaults" button to pull in the new campaign defaults.
                        </MDBAlert>

                        <MDBBtn color="primary" size="sm" class="mb-3" @click="populateDefaults" :disabled="!modalValuesLocal.company_id || !modalValuesLocal.product_id || isPopulating">Populate
                            Defaults
                            <MDBSpinner tag="span" size="sm" v-if="isPopulating" class="ms-2"/>
                        </MDBBtn>

                        <SmartSelectAjax label="Payment Type" ajax-url="options/dialer_payment_types" v-model:selected="modalValuesLocal.payment_type_id" :show-required="true"/>

                        <MDBInput v-if="authStore.hasAccessToArea('ACCESS_AREA_BILLABLE_RATES')" label="Billable Rate (US$)" v-model="modalValuesLocal.billable_rate"/>

                        <MDBInput label="Payable Rate (US$)" v-model="modalValuesLocal.payable_rate"/>

                        <MDBInput label="Bonus Rate (US$)" v-model="modalValuesLocal.bonus_rate"/>

                        <MDBDatepicker v-model="modalValuesLocal.start_date" inputToggle label="Start Date" format="YYYY-MM-DD" confirmDateOnSelect :required="true"/>

                        <MDBCheckbox label="Training" v-model="modalValuesLocal.is_training"/>

                        <MDBDatepicker v-model="modalValuesLocal.end_date" inputToggle label="End Date" format="YYYY-MM-DD" confirmDateOnSelect/>

                        <SmartSelectAjax v-if="modalValuesLocal.end_date" label="Termination Reason" ajax-url="options/termination_reasons" v-model:selected="modalValuesLocal.termination_reason_id"
                                         :show-required="true"/>
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
            <MDBBtn outline="dark" @click="showModalLocal = false">Cancel</MDBBtn>
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
import apiClient from "../../http";
import {ref, onMounted, watch, computed} from 'vue';
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import {authStore} from "@/store/auth-store";
import {toast} from "vue3-toastify";

const props = defineProps({
    title: String,
    showModal: Boolean,
    modalValues: Object,
});

const emit = defineEmits([
    'update:showModal',
    'update:modalValues',
]);

const isSaving = ref(false);
const isPopulating = ref(false);
const campaignSwitched = ref(false);

const modalTitle = computed(() => {
    return (props.modalValues.id ? 'Edit' : 'Add') + ' Effective Date';
})

const save = () => {
    isSaving.value = true;

    const payload = {
        ...modalValuesLocal.value,
        companies_list: Array.isArray(modalValuesLocal.value.companies_list) ? modalValuesLocal.value.companies_list.join(",") : modalValuesLocal.value.companies_list,
    };

    if (modalValuesLocal.value.id) {
        apiClient.patch(`agents/effective-dates/${modalValuesLocal.value.id}`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    } else {
        apiClient.post(`agents/effective-dates`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    }
}

const populateDefaults = () => {
    isPopulating.value = true;
    apiClient.get(`agents/campaign-defaults/${modalValuesLocal.value.company_id}/${modalValuesLocal.value.product_id}`)
        .then(({data}) => {
            if (data.id) {
                modalValuesLocal.value.payment_type_id = data.payment_type_id;
                if (authStore.hasAccessToArea("ACCESS_AREA_BILLABLE_RATES")) {
                    modalValuesLocal.value.billable_rate = data.billable_rate;
                }
                modalValuesLocal.value.payable_rate = data.payable_rate;
                modalValuesLocal.value.bonus_rate = data.bonus_rate;
            } else {
                toast.error("No default values are defined for this call center and campaign combination.");
            }
        }).finally(() => {
        isPopulating.value = false;
    });
}
const showModalLocal = useModelWrapper(props, emit, 'showModal');
const modalValuesLocal = useModelWrapper(props, emit, 'modalValues');

watch(() => showModalLocal.value, () => {
    // Reset the warning message if the modal state changes
    campaignSwitched.value = false;
});
</script>
<style lang="scss">
</style>
