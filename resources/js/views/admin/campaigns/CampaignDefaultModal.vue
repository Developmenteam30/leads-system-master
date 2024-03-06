<template>
    <MDBModal
        id="defaultModal"
        tabindex="-1"
        labelledby="defaultModalLabel"
        v-model="showModalLocal"
        class="modal-input-spacing"
        @shown="onModalOpen"
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

                        <div class="row">
                            <div class="col-6">
                                <MDBRadio  label="Flat Rate" v-model="modalValuesLocal.bonus_type"  :value="'flat_rate'" />
                            </div>
                            <div class="col-6">
                                <MDBRadio  label="Tier Based" v-model="modalValuesLocal.bonus_type" :value="'tier_based'" />
                            </div>
                        </div>

                        <div v-if="!modalValuesLocal.bonus_type || (modalValuesLocal.bonus_type && modalValuesLocal.bonus_type == 'flat_rate')">
                            <MDBInput label="Bonus Rate (US$)" v-model="modalValuesLocal.bonus_rate"/>
                        </div>
                        <div v-if="(modalValuesLocal.bonus_type && modalValuesLocal.bonus_type == 'tier_based')">
                            <div v-for="(item, index) in Bonus" :key="index" class="row">
                                <div class="col-5">
                                    <MDBInput label="Limit" v-model="item.limit"/>
                                </div>
                                <div class="col-5">
                                    <MDBInput label="Bonus Rate (US$)" v-model="item.value"/>
                                </div>
                                <div class="col-2">
                                    <MDBBtn  color="danger" @click="removeRowBonus(index)">X</MDBBtn>
                                </div>
                                <hr />
                            </div>
                            <MDBBtn v-if="Bonus.length < 5" color="primary" @click="addRowBonus">Add New Row</MDBBtn>
                            <hr />
                        </div>

                        <MDBInput v-if="authStore.hasAccessToArea('ACCESS_AREA_BILLABLE_RATES')" label="Billable Training Rate (US$)" v-model="modalValuesLocal.billable_training_rate"/>

                        <MDBInput label="Payable Training Rate (US$)" v-model="modalValuesLocal.payable_training_rate"/>

                        <MDBInput label="Training Duration ( in days )"  type="number" v-model="modalValuesLocal.training_duration"/>
                        <h5>Special Billable Rates</h5>
                        <hr />
                        <div v-if="items">
                            <div v-for="(item, index) in items" :key="index" class="row">
                                <div class="col-6">
                                    <MDBDatepicker label="Start Date" v-model="item.formattedStartDate" inputToggle confirmDateOnSelect format="YYYY-MM-DD" :required="true"/>
                                </div>
                                <div class="col-6">
                                    <MDBDatepicker label="End Date" v-model="item.formattedEndDate" inputToggle confirmDateOnSelect format="YYYY-MM-DD" :required="true"/>
                                </div>
                                <div class="col-6">
                                    <MDBInput v-if="authStore.hasAccessToArea('ACCESS_AREA_BILLABLE_RATES')" label="Billable Rate (US$)" v-model="item.billable_rate"/>
                                </div>
                                <div class="col-6">
                                    <MDBBtn  color="danger" @click="removeRow(index)">Remove</MDBBtn>
                                </div>
                                <hr />
                            </div>
                        </div>
                        <MDBBtn v-if="items.length < 5" color="primary" @click="addRow">Add New Row</MDBBtn>

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
    MDBRadio,
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
function onModalOpen()  {
    if(modalValuesLocal.value.tier_bonus_rates && modalValuesLocal.value.tier_bonus_rates != '' && modalValuesLocal.value.tier_bonus_rates != 'null'){
        Bonus.value = JSON.parse(modalValuesLocal.value.tier_bonus_rates);
    }
    if(modalValuesLocal.value.special_billing_rates && modalValuesLocal.value.special_billing_rates != '' && modalValuesLocal.value.special_billing_rates != 'null'){
        items.value = JSON.parse(modalValuesLocal.value.special_billing_rates);
    }
}
const addRow = () => {
    items.value = [...items.value, { formattedStartDate: '', formattedEndDate: '', billable_rate: '' }];
}
const removeRow = (index) => {
    items.value.splice(index, 1);
}

const addRowBonus = () => {
    Bonus.value = [...Bonus.value, { limit: 5, value: 0 }];
}
const removeRowBonus = (index) => {
    Bonus.value.splice(index, 1);
}

const Bonus = ref([]);
const items = ref([]);
const isSaving = ref(false);
const isPopulating = ref(false);

const modalTitle = computed(() => {
    return (props.modalValues.id ? 'Edit' : 'Add') + ' Call Center Default Values';
})

const save = () => {
    isSaving.value = true;
    var bb = [];
    if(Bonus.value.length > 0){
        Bonus.value.forEach(e=>{
            if(e.limit && e.value){
                bb.push(e);
            }
        })
    }
    modalValuesLocal.value.tier_bonus_rates = JSON.stringify(bb);
    var sr = [];
    if(items.value.length > 0){
        items.value.forEach(e=>{
            if(e.formattedStartDate && e.formattedStartDate != '' && e.formattedEndDate && e.formattedEndDate != '' && e.billable_rate){
                sr.push(e);
            }
        })
    }
    modalValuesLocal.value.special_billing_rates = JSON.stringify(sr);
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
