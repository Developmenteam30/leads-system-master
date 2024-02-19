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
            <MDBModalTitle id="editHoursModalLabel">Edit Hours - {{ modalValuesLocal.agent_name }} ({{ modalValuesLocal.internal_campaign_name }})</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <MDBTabs v-model="activeTab">
                <!-- Tabs navs -->
                <MDBTabNav pills justify tabsClasses="mb-3 text-center flex-md-row flex-column">
                    <MDBTabItem tag="button" :wrap="false" tabId="hours" class="flex-fill">Hours</MDBTabItem>
                    <MDBTabItem tag="button" :wrap="false" tabId="bonuses" class="flex-fill">Bonuses</MDBTabItem>
                </MDBTabNav>
                <!-- Tabs navs -->

                <!-- Tabs content -->
                <MDBTabContent>
                    <MDBTabPane tabId="hours">
                        <template v-for="(label, key, index) in weekdays">
                            <MDBRow class="align-items-center">
                                <MDBCol col="3" lg="2" xl="1">
                                    <h4>{{ label }}</h4>
                                </MDBCol>
                                <MDBCol col="3" lg="2" xl="1">
                                    <h4>{{ sumHours(modalValuesLocal, key) }}</h4>
                                </MDBCol>
                                <MDBCol col="6" lg="4" xl="2">
                                    <MDBInput label="Regular Hours" v-model="modalValuesLocal[`${key}_editable_hours`]" :disabled="modalValuesLocal[`${key}_disabled`]"/>
                                </MDBCol>
                                <MDBCol col="6" lg="4" xl="2" v-if="!isPayrollReport && form.view === 'billable'">
                                    <MDBInput label="Break Minutes" v-model="modalValuesLocal[`${key}_break_minutes`]" :disabled="true"/>
                                </MDBCol>
                                <MDBCol col="6" lg="4" xl="2" v-if="form.view === 'payable'">
                                    <MDBInput label="Huddle Minutes" v-model="modalValuesLocal[`${key}_huddle_minutes`]" :disabled="modalValuesLocal[`${key}_disabled`]"/>
                                </MDBCol>
                                <MDBCol col="6" lg="4" xl="2">
                                    <MDBInput label="Coaching Minutes" v-model="modalValuesLocal[`${key}_coaching_minutes`]" :disabled="modalValuesLocal[`${key}_disabled`]"/>
                                </MDBCol>
                                <MDBCol col="6" lg="4" xl="2">
                                    <MDBCheckbox label="Training" v-model="modalValuesLocal[`${key}_training`]" disabled/>
                                    <MDBCheckbox label="Holiday" v-model="modalValuesLocal[`${key}_holiday`]" disabled/>
                                </MDBCol>
                            </MDBRow>
                            <hr v-if="index !== Object.keys(weekdays).length - 1"/>
                        </template>
                    </MDBTabPane>

                    <MDBTabPane tabId="bonuses">
                        <MDBRow class="align-items-center">
                            <MDBCol col="3" lg="2" xl="1" class="text-center"><h5>Day</h5></MDBCol>
                            <MDBCol col="3" lg="2" xl="2" class="text-center"><h5>Amount</h5></MDBCol>
                            <MDBCol col="3" lg="2" xl="1" class="text-center"><h5>BTs</h5></MDBCol>
                            <MDBCol col="3" lg="2" xl="2" class="text-center"><h5>Rate</h5></MDBCol>
                            <MDBCol col="3" lg="2" xl="2" class="text-center"><h5>Agent Avg</h5></MDBCol>
                            <MDBCol col="3" lg="2" xl="2" class="text-center"><h5>Company Avg</h5></MDBCol>
                            <MDBCol col="3" lg="2" xl="2" class="text-center"><h5>Bonus Level</h5></MDBCol>
                        </MDBRow>
                        <template v-for="(label, key, index) in weekdays">
                            <MDBRow class="align-items-center">
                                <MDBCol col="3" lg="2" xl="1" class="text-center">
                                    <h4>{{ label }}</h4>
                                </MDBCol>
                                <MDBCol col="3" lg="2" xl="2" class="text-center">
                                    <h4>{{ formatCurrency(modalValuesLocal[`${key}_bonus_amount`]) }}</h4>
                                </MDBCol>
                                <MDBCol col="3" lg="2" xl="1" class="text-center">
                                    <h4>{{ modalValuesLocal[`${key}_billable_transfers`] }}</h4>
                                </MDBCol>
                                <MDBCol col="3" lg="2" xl="2" class="text-center">
                                    <h4>{{ formatCurrency(modalValuesLocal[`${key}_effective_bonus_rate`]) }}</h4>
                                </MDBCol>
                                <MDBCol col="3" lg="2" xl="2" class="text-center">
                                    <h4>{{ formatSecondsAsHuman(modalValuesLocal[`${key}_agent_average`]) }}</h4>
                                </MDBCol>
                                <MDBCol col="3" lg="2" xl="2" class="text-center">
                                    <h4>{{ formatSecondsAsHuman(modalValuesLocal[`${key}_company_average`]) }}</h4>
                                </MDBCol>
                                <MDBCol col="3" lg="2" xl="2" class="text-center">
                                    <h4>{{ modalValuesLocal[`${key}_bonus_level`] }}</h4>
                                </MDBCol>
                            </MDBRow>
                            <hr v-if="index !== Object.keys(weekdays).length - 1"/>
                        </template>
                    </MDBTabPane>
                </MDBTabContent>
                <!-- Tabs content -->
            </MDBTabs>
        </MDBModalBody>
        <MDBModalFooter>
            <MDBBtn outline="dark" @click="showModalLocal = false">Cancel</MDBBtn>
            <MDBBtn color="primary" @click="saveHours">Save changes
                <MDBSpinner tag="span" size="sm" v-if="isSaving" class="ms-2"/>
            </MDBBtn>
        </MDBModalFooter>
    </MDBModal>
</template>

<script setup>
import {ref} from "vue";
import {useModelWrapper} from "@/modelWrapper";
import {
    MDBBtn, MDBCheckbox, MDBCol,
    MDBInput,
    MDBModal,
    MDBModalBody,
    MDBModalFooter,
    MDBModalHeader,
    MDBModalTitle,
    MDBRow,
    MDBSpinner,
    MDBTabContent,
    MDBTabItem,
    MDBTabNav,
    MDBTabPane,
    MDBTabs,
} from "mdb-vue-ui-kit";
import apiClient from "../../http";
import {formatNumberDecimals} from "@/helpers";
import {round} from "lodash";
import {formatCurrency, formatSecondsAsHuman} from "@/bootstrap";

const emit = defineEmits([
    'update:showModal',
    'reload',
]);

const props = defineProps({
    showModal: Boolean,
    modalValues: Object,
    form: Object,
    weekdays: Object,
    isPayrollReport: Boolean,
});

const isSaving = ref(false);
const activeTab = ref('hours');

const saveHours = () => {
    isSaving.value = true;

    apiClient.patch(`reports/payroll/${modalValuesLocal.value.agent_id}`, {
        ...formLocal.value,
        ...modalValuesLocal.value,
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
const modalValuesLocal = useModelWrapper(props, emit, 'modalValues');
const formLocal = useModelWrapper(props, emit, 'form');

const sumHours = (modalValues, key) => {
    return formatNumberDecimals(round(Number(modalValues[`${key}_editable_hours`] ?? 0) +
        round(Number(modalValues[`${key}_break_minutes`] ?? 0) / 60, 2) +
        round(Number(modalValues[`${key}_coaching_minutes`] ?? 0) / 60, 2) +
        round(Number(modalValues[`${key}_huddle_minutes`] ?? 0) / 60, 2), 2));
}
</script>
