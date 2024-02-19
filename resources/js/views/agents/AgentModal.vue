<template>
    <MDBModal
        id="editAgentModal"
        tabindex="-1"
        labelledby="editAgentModalLabel"
        v-model="showModalLocal"
        class="modal-input-spacing"
        staticBackdrop
        size="xl"
    >
        <MDBModalHeader>
            <MDBModalTitle id="editAgentModalLabel">{{ modalTitle }}</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <Suspense>
                <template #default>
                    <div>
                        <MDBInput :label="`First and Last Name`" v-model="modalValuesLocal.agent_name" :required="true" autocomplete="off"/>

                        <SmartSelectAjax label="Call Center" ajax-url="options/companies" v-model:selected="modalValuesLocal.company_id" :show-required="true"/>

                        <SmartSelectAjax v-if="'Employee' === title" label="Client Assignment" ajax-url="options/client_companies" v-model:selected="modalValuesLocal.companies_list" multiple/>

                        <SmartSelectAjax v-if="'User' !== title" label="Team" ajax-url="options/dialer_teams" v-model:selected="modalValuesLocal.team_id"/>

                        <MDBDatepicker v-if="'User' !== title" v-model="modalValuesLocal.date_of_birth" inputToggle label="Date of Birth" format="YYYY-MM-DD" confirmDateOnSelect/>

                        <MDBInput label="Email Address" v-model="modalValuesLocal.email" type="email" autocomplete="off"/>

                        <template v-if="authStore.hasAccessToArea('ACCESS_AREA_MANAGE_ACCESS_DETAILS')">
                            <h5 class="my-3">Access Details</h5>
                            <SmartSelectAjax v-if="'Agent' !== title" label="Access Role" ajax-url="options/access_roles" v-model:selected="modalValuesLocal.access_role_id"/>

                            <MDBInput label="Password" v-model="modalValuesLocal.password" type="password" autocomplete="new-password"/>

                            <MDBCheckbox label="Send welcome email" v-model="modalValuesLocal.send_welcome_email" :disabled="!modalValuesLocal.email"/>
                        </template>

                        <MDBBtn
                            v-if="authStore.hasAccessToArea('ACCESS_AREA_LOGIN_IMPERSONATION')"
                            color="primary"
                            size="sm"
                            @click="impersonate(modalValuesLocal.id)"
                        >Login as {{ props.title }}
                        </MDBBtn>

                        <template v-if="modalValuesLocal.id">
                            <h5 class="mt-3">Effective Dates</h5>

                            <MDBBtn color="primary" size="sm" @click="showEffectiveDateModal(null)" :disabled="openEndedStatus">Add new Effective Date</MDBBtn>
                            <MDBAlert color="warning" static class="mt-2" v-if="openEndedStatus">Before adding a new effective date, you must set end dates on all the existing entries.</MDBAlert>

                            <DataField
                                class="mb-0 mt-2"
                                label="First Date in Dialer"
                                v-if="modalValuesLocal && modalValuesLocal.first_performance_date && modalValuesLocal.first_performance_date.file_date"
                                :value="modalValuesLocal.first_performance_date.file_date"
                            >
                            </DataField>
                            <CustomDatatableAjax
                                :ajax-url="`agents/effective-dates/${modalValuesLocal.id}`"
                                :entries="10000"
                                striped
                                fixedHeader
                                class="mt-1"
                                :pagination="false"
                                clickableRows
                                :key="effectiveDateKey"
                                @row-click-values="showEffectiveDateModal"
                                :auto-height="false"
                            />
                        </template>

                        <template v-if="modalValuesLocal.id && 'User' !== title">
                            <MDBAccordion v-model="activeItem" stayOpen class="mt-4">
                                <MDBAccordionItem
                                    headerTitle="Evaluations"
                                    collapseId="evaluations"
                                >
                                    <h5 class="mt-3">Evaluations</h5>
                                    <EvaluationsDatatable :ajax-url="`evaluations/agent/${modalValuesLocal.id}`"/>
                                </MDBAccordionItem>

                                <MDBAccordionItem
                                    headerTitle="Write Ups"
                                    collapseId="writeups"
                                >
                                    <WriteupListAgent
                                        :agent-id="modalValuesLocal.id"
                                        :embedded="true"
                                        :show-add-button="authStore.hasAccessToArea('ACCESS_AREA_MENU_PEOPLE_WRITEUPS_AGENTS')"
                                    />
                                </MDBAccordionItem>

                                <MDBAccordionItem
                                    headerTitle="Performance Improvement Plans"
                                    collapseId="pips"
                                >
                                    <PipList
                                        :agent-id="modalValuesLocal.id"
                                        :embedded="true"
                                        :show-add-button="false"
                                        :key="modalValuesLocal.id"
                                        :exportable="false"
                                    ></PipList>
                                </MDBAccordionItem>

                                <MDBAccordionItem
                                    headerTitle="Leave Requests"
                                    collapseId="leave-requests"
                                >
                                    <LeaveRequestListAgent
                                        :agent-id="modalValuesLocal.id"
                                        :embedded="true"
                                        v-if="authStore.hasAccessToArea('ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS')"
                                    ></LeaveRequestListAgent>
                                </MDBAccordionItem>

                                <MDBAccordionItem
                                    headerTitle="Documents"
                                    collapseId="documents"
                                >
                                    <AgentDocumentList
                                        :agent-id="modalValuesLocal.id"
                                        :embedded="true"
                                        v-if="authStore.hasAccessToArea('ACCESS_AREA_MENU_PEOPLE_DOCUMENTS')"
                                    ></AgentDocumentList>
                                </MDBAccordionItem>
                            </MDBAccordion>
                        </template>
                        <template v-else-if="'User' !== title">
                            <h5 class="my-3">Initial Effective Date Details</h5>
                            <MDBDatepicker v-model="modalValuesLocal.start_date" inputToggle label="Hire Date" format="YYYY-MM-DD" confirmDateOnSelect :required="true"/>

                            <MDBCheckbox label="Training" v-model="modalValuesLocal.is_training"/>

                            <SmartSelectAjax label="Campaign" ajax-url="options/dialer_products" v-model:selected="modalValuesLocal.product_id" :show-required="true"/>

                            <MDBBtn color="primary" size="sm" class="mb-3" @click="populateDefaults" :disabled="!modalValuesLocal.company_id || !modalValuesLocal.product_id || isPopulating">Populate
                                Defaults
                                <MDBSpinner tag="span" size="sm" v-if="isPopulating" class="ms-2"/>
                            </MDBBtn>

                            <SmartSelectAjax label="Payment Type" ajax-url="options/dialer_payment_types" v-model:selected="modalValuesLocal.payment_type_id" :show-required="true"/>

                            <MDBInput v-if="authStore.hasAccessToArea('ACCESS_AREA_BILLABLE_RATES')" label="Billable Rate (US$)" v-model="modalValuesLocal.billable_rate"/>

                            <MDBInput label="Payable Rate (US$)" v-model="modalValuesLocal.payable_rate"/>

                            <MDBInput label="Bonus Rate (US$)" v-model="modalValuesLocal.bonus_rate"/>
                        </template>
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
    <ManageEffectiveDate
        :showModal="effectiveDateModal"
        @update:showModal="updateEffectiveDateModal"
        :title="title"
        :modalValues="effectiveDateModalValues"
    >
    </ManageEffectiveDate>
</template>

<script setup>
import {useModelWrapper} from "@/modelWrapper";
import {
    MDBAccordion,
    MDBAccordionItem,
    MDBAlert,
    MDBBtn, MDBCheckbox,
    MDBDatepicker,
    MDBInput,
    MDBModal,
    MDBModalBody,
    MDBModalFooter,
    MDBModalHeader,
    MDBModalTitle,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import apiClient from "../../http";
import {ref, onUpdated, computed, nextTick} from 'vue';
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import ManageEffectiveDate from "./ManageEffectiveDate.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import {authStore} from "@/store/auth-store";
import {toast} from "vue3-toastify";
import EvaluationsDatatable from "@/components/EvaluationsDatatable.vue";
import WriteupListAgent from "@/views/writeups/WriteupListAgent.vue";
import DataField from "@/components/DataField.vue";
import AgentDocumentList from "@/views/documents/AgentDocumentList.vue";
import LeaveRequestListAgent from "@/views/leave-requests/LeaveRequestListAgent.vue";
import router from "../../routes";
import PipList from "@/views/people/pips/PipList.vue";

const emit = defineEmits([
    "reload",
    "update:showModal",
]);

const props = defineProps({
    title: String,
    showModal: Boolean,
    modalValues: Object,
});

const effectiveDateKey = ref(0);
const effectiveDateModal = ref(false);
const effectiveDateModalValues = ref({});
const openEndedStatus = ref(false);
const isSaving = ref(false);
const writeupModal = ref(false);
const writeupModalValues = ref('');
const isPopulating = ref(false);
const activeItem = ref('');

const modalTitle = computed(() => {
    return props.modalValues.id ? `Edit ${props.title} - ${props.title} ID ${props.modalValues.id}` : `Add an ${props.title}`;
})

onUpdated(() => {
    getOpenEndedStatus();
});

const getOpenEndedStatus = () => {
    if (modalValuesLocal.value.id) {
        apiClient.get(`agents/effective-dates/${modalValuesLocal.value.id}/open-ended`)
            .then(({data}) => {
                openEndedStatus.value = data.value;
            });
    } else {
        openEndedStatus.value = false;
    }
}

const save = () => {
    isSaving.value = true;

    const payload = {
        ...modalValuesLocal.value,
        companies_list: Array.isArray(modalValuesLocal.value.companies_list) ? modalValuesLocal.value.companies_list.join(",") : modalValuesLocal.value.companies_list,
        title: props.title,
    };

    if (modalValuesLocal.value.id) {
        apiClient.patch(`agents/manage/${modalValuesLocal.value.id}`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
                emit("reload");
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    } else {
        apiClient.post(`agents/manage`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
                emit("reload");
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    }
}

const showEffectiveDateModal = (values) => {
    if (values !== null) {
        effectiveDateModalValues.value = {
            ...values,
            company_id: modalValuesLocal.value.company_id,
            error: '',
        };
    } else {
        effectiveDateModalValues.value = {
            id: null,
            agent_id: modalValuesLocal.value.id,
            company_id: modalValuesLocal.value.company_id,
            is_training: 1,
            error: '',
        };
    }
    effectiveDateModal.value = true;
}

const updateEffectiveDateModal = (value) => {
    effectiveDateModal.value = value;
    if (value === false) {
        nextTick(() => {
            effectiveDateKey.value++;
        });
    }
}

const showWriteupModal = (id) => {
    writeupModalValues.value = id;
    writeupModal.value = true;
}

const updateWriteupModal = (value) => {
    writeupModal.value = value;
}

const populateDefaults = () => {
    isPopulating.value = true;
    apiClient.get(`agents/campaign-defaults/${modalValuesLocal.value.company_id}/${modalValuesLocal.value.product_id}`)
        .then(({data}) => {
            if (data.id) {
                modalValuesLocal.value.payment_type_id = data.payment_type_id;
                if (authStore.hasAccessToArea('ACCESS_AREA_BILLABLE_RATES')) {
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

const impersonate = (agent_id) => {
    apiClient.post(`login/impersonate/${agent_id}`).then(({data}) => {
        if (data.token) {
            authStore.setToken(data.token, data.accessAreas ?? [], data.agent ?? {});
            router.push({name: 'dashboard'});
        }
    }).catch((error) => {
    }).finally(() => {
    });
};

const showModalLocal = useModelWrapper(props, emit, 'showModal');
const modalValuesLocal = useModelWrapper(props, emit, 'modalValues');
</script>
<style lang="scss">
.accordion-button {
    font-weight: bold;
    font-size: 1rem;
}
</style>
