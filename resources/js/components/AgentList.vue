<template>
    <h2>Manage {{ title }}s</h2>
    <AsyncPage>
        <MDBContainer fluid class="mt-3 p-0 m-0">
            <FilterRow>
                <FilterColumn>
                    <MDBInput v-model="form.search" :label="`Search by ${title} Name`" :readonly="isLoading"/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="400px">
                    <SmartSelectAjax label="Call Center" :disabled="isLoading" ajax-url="options/companies" v-model:selected="form.company_ids" multiple/>
                </FilterColumn>
                <FilterColumn>
                    <SmartSelectAjax label="Campaign" :disabled="isLoading" ajax-url="options/dialer_products" v-model:selected="form.product_id"/>
                </FilterColumn>
                <FilterColumn>
                    <SmartSelectAjax label="Team" :disabled="isLoading" ajax-url="options/dialer_teams" v-model:selected="form.team_id"/>
                </FilterColumn>
                <FilterColumn :last="('Agent' === title)">
                    <SmartSelectAjax label="Status" :disabled="isLoading" ajax-url="options/dialer_agent_statuses" v-model:selected="form.statuses" multiple/>
                </FilterColumn>
                <FilterColumn v-if="'Employee' === title && authStore.hasAccessToArea('ACCESS_AREA_ADD_EDIT_EMPLOYEES')">
                    <MDBBtn color="primary" @click="showAgentModal(null)">Add an Employee</MDBBtn>
                </FilterColumn>
                <FilterColumn v-if="'User' === title && authStore.hasAccessToArea('ACCESS_AREA_ADD_EDIT_USERS')">
                    <MDBBtn color="primary" @click="showAgentModal(null)">Add a User</MDBBtn>
                </FilterColumn>
                <FilterColumn v-if="authStore.hasAccessToArea('ACCESS_AREA_AGENT_BULK_EDIT')">
                    <MDBBtn color="primary" @click="showBulkUpdate(null)" :disabled="!selectedRows || selectedRows.length < 2">Bulk Edit</MDBBtn>
                </FilterColumn>
                <FilterColumn last>
                    <MDBBtn color="primary" @click="showCounts = !showCounts" :disabled="isLoading"><span v-if="showCounts">Hide</span><span v-else>Show</span> Counts</MDBBtn>
                </FilterColumn>
            </FilterRow>
        </MDBContainer>
        <MDBAlert color="danger" static v-if="error">{{ error }}</MDBAlert>
        <MDBContainer v-if="showCounts">
            <MDBRow class="mt-3">
                <div class="flex-grow-1 text-center" v-if="isLoading">
                    <MDBSpinner/>
                </div>
                <template v-if="!isLoading && dataset.counts">
                    <MDBCol md="4">
                        <MDBCard>
                            <MDBCardHeader><h4 class="mb-0">Campaign</h4></MDBCardHeader>
                            <MDBListGroup flush>
                                <MDBListGroupItem v-for="(count, campaign) in dataset.counts.campaign" :key="campaign">
                                    <div class="d-flex justify-content-between">
                                        <div class="text-left w-75">
                                            <h6 class="mb-0">{{ campaign }}</h6>
                                        </div>
                                        <div class="text-center w-25">
                                            <h6 class="mb-0">{{ count }}</h6>
                                        </div>
                                    </div>
                                </MDBListGroupItem>
                            </MDBListGroup>
                        </MDBCard>
                    </MDBCol>
                    <MDBCol md="4">
                        <MDBCard>
                            <MDBCardHeader><h4 class="mb-0">Call Center</h4></MDBCardHeader>
                            <MDBListGroup flush>
                                <MDBListGroupItem v-for="(count, campaign) in dataset.counts.call_center" :key="campaign">
                                    <div class="d-flex justify-content-between">
                                        <div class="text-left w-75">
                                            <h6 class="mb-0">{{ campaign }}</h6>
                                        </div>
                                        <div class="text-center w-25">
                                            <h6 class="mb-0">{{ count }}</h6>
                                        </div>
                                    </div>
                                </MDBListGroupItem>
                            </MDBListGroup>
                        </MDBCard>
                    </MDBCol>
                    <MDBCol md="4">
                        <MDBCard>
                            <MDBCardHeader><h4 class="mb-0">Team</h4></MDBCardHeader>
                            <MDBListGroup flush>
                                <MDBListGroupItem v-for="(count, campaign) in dataset.counts.team" :key="campaign">
                                    <div class="d-flex justify-content-between">
                                        <div class="text-left w-75">
                                            <h6 class="mb-0">{{ campaign }}</h6>
                                        </div>
                                        <div class="text-center w-25">
                                            <h6 class="mb-0">{{ count }}</h6>
                                        </div>
                                    </div>
                                </MDBListGroupItem>
                            </MDBListGroup>
                        </MDBCard>
                    </MDBCol>
                </template>
            </MDBRow>
        </MDBContainer>
        <CustomDatatableAjax
            :ajax-url="`agents/manage`"
            :ajax-payload="{ ...form, title: $props.title }"
            class="mt-3"
            :clickableRows="authStore.hasAccessToArea('ACCESS_AREA_ADD_EDIT_AGENTS')"
            @row-click-values="showAgentModal"
            @update:loading="(value) => isLoading = value"
            :exportable="true"
            :show-count="true"
            @selected-rows="setSelectedRows"
            @dataset="(value) => dataset = value"
        />
    </AsyncPage>
    <AgentModal
        :showModal="agentModal"
        @update:showModal="(value) => agentModal = value"
        @reload="reloadResults"
        :modalValues="agentModalValues"
        :title="title"
    >
    </AgentModal>
    <AgentBulkModal
        :showModal="agentBulkModal"
        @update:showModal="(value) => agentBulkModal = value"
        @reload="reloadResults"
        :title="title"
        :agent-ids="selectedRows"
    >
    </AgentBulkModal>
</template>

<script setup>
import SmartSelectAjax from "./SmartSelectAjax.vue";
import {
    MDBAlert,
    MDBBtn,
    MDBContainer,
    MDBInput,
    MDBCard,
    MDBCardHeader,
    MDBCol,
    MDBListGroup,
    MDBListGroupItem,
    MDBSpinner,
    MDBRow
} from "mdb-vue-ui-kit";

import {ref} from 'vue';
import {authStore} from "@/store/auth-store.ts";
import CustomDatatableAjax from "./CustomDatatableAjax.vue";
import AgentModal from "../views/agents/AgentModal.vue";
import AgentBulkModal from "@/views/agents/AgentBulkModal.vue";
import {value} from "lodash/seq";
import FilterColumn from "./FilterColumn.vue";
import FilterRow from "./FilterRow.vue";
import AsyncPage from "@/components/AsyncPage.vue";

const props = defineProps({
    title: String,
});

const selectedRows = ref([]);

const showCounts = ref(false);
const agentModal = ref(false);
const agentBulkModal = ref(false);
const agentModalValues = ref({});
const error = ref('');
const form = ref({
    statuses: '1',
    company_ids: '',
    product_id: '',
    team_id: '',
    search: '',
    key: 0,
});
const isLoading = ref(false);
const dataset = ref({});

const showAgentModal = (values) => {
    if (!authStore.hasAccessToArea("ACCESS_AREA_ADD_EDIT_AGENTS")) {
        return;
    }

    if (values !== null) {
        agentModalValues.value = values;
    } else {
        agentModalValues.value = {
            id: null,
            agent_type_id: 2, // VISIBLE_EMPLOYEE
            company_id: 16,
            is_active: true,
        };
    }
    agentModal.value = true;
}

const showBulkUpdate = (values) => {
    if (!authStore.hasAccessToArea('ACCESS_AREA_ADD_EDIT_AGENTS')) {
        return;
    }

    agentBulkModal.value = true;
}

const reloadResults = () => {
    form.value.key++;
    selectedRows.value = [];
}

const setSelectedRows = (value) => {
    selectedRows.value = [];
    if (value && value.length) {
        value.forEach(row => {
            selectedRows.value.push(row.id);
        });
    }
}
</script>
<style lang="scss">
</style>
