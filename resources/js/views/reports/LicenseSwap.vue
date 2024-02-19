<template>
    <h2>License Swap Report</h2>
    <AsyncPage>
        <template v-if="isLoading">
            <div class="d-flex justify-content-center">
                <div class="flex-grow-1 text-center">
                    <MDBSpinner/>
                </div>
            </div>
        </template>
        <template v-else>
            <MDBRow class="my-3">
                <MDBCol col="12" xl="2">
                    <MDBCard text="center" class="bg-opacity-25 bg-info">
                        <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Last Updated</h5></MDBCardHeader>
                        <MDBCardBody class="p-2">
                            <MDBCardText><h3>{{ updatedDate }}</h3></MDBCardText>
                            <MDBBtn color="primary" @click="getValues(true)">Refresh</MDBBtn>
                        </MDBCardBody>
                    </MDBCard>
                </MDBCol>
                <MDBCol col="12" xl="10">
                    <MDBRow class="my-3">
                        <MDBCol col="12" md="6" lg="3">
                            <MDBCard text="center" class="bg-opacity-25 bg-info">
                                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Paid Licenses</h5></MDBCardHeader>
                                <MDBCardBody class="p-2">
                                    <MDBCardText><h3>{{ dataset.data?.summary.total }}</h3></MDBCardText>
                                </MDBCardBody>
                            </MDBCard>
                        </MDBCol>
                        <MDBCol col="12" md="6" lg="3">
                            <MDBCard text="center" class="bg-opacity-25 bg-info mt-3 mt-md-0">
                                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Assigned Licenses</h5></MDBCardHeader>
                                <MDBCardBody class="p-2">
                                    <MDBCardText><h3>{{ dataset.data?.summary.assigned }}</h3></MDBCardText>
                                </MDBCardBody>
                            </MDBCard>
                        </MDBCol>
                        <MDBCol col="12" md="6" lg="3" class="mt-3 mt-lg-0">
                            <MDBCard text="center" class="bg-opacity-25 bg-info">
                                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Unassigned Available</h5></MDBCardHeader>
                                <MDBCardBody class="p-2">
                                    <MDBCardText><h3>{{ dataset.data?.summary.unassigned_available }}</h3></MDBCardText>
                                </MDBCardBody>
                            </MDBCard>
                        </MDBCol>
                        <MDBCol col="12" md="6" lg="3" class="mt-3 mt-lg-0">
                            <MDBCard text="center" class="bg-opacity-25 bg-info">
                                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Unassigned Cooldown</h5></MDBCardHeader>
                                <MDBCardBody class="p-2">
                                    <MDBCardText><h3>{{ dataset.data?.summary.unassigned_cooldown }}</h3></MDBCardText>
                                </MDBCardBody>
                            </MDBCard>
                        </MDBCol>
                    </MDBRow>
                </MDBCol>
            </MDBRow>

            <MDBRow class="my-3">
                <MDBCol col="4">
                    <MDBCard text="center" class="bg-opacity-25">
                        <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Licenses Available to be Reassigned</h5></MDBCardHeader>
                        <MDBCardBody class="p-2">
                            <MDBCardText><h3>{{ dataset.data?.available_licenses.length }}</h3></MDBCardText>
                            <MDBListGroup flush>
                                <MDBListGroupItem v-for="(agent, index) in dataset.data?.available_licenses">
                                    {{ agent.user_id }} - {{ agent.name }}
                                    <SmartSelect label="Assign to Agent" v-model:options="availableAgents" filter clearButton :preselect="false" v-model:selected="agent_ids[index]"
                                                 class="agent-selector"/>
                                </MDBListGroupItem>
                            </MDBListGroup>
                            <MDBAlert color="danger" class="mt-2" static v-if="hasDuplicates">One or more licenses are reassigned to the same agent.</MDBAlert>
                            <MDBBtn color="primary" @click="assignLicense" :disabled="hasDuplicates">
                                Assign
                                <MDBSpinner tag="span" size="sm" v-if="isSending" class="ms-2"/>
                            </MDBBtn>
                        </MDBCardBody>
                    </MDBCard>
                </MDBCol>
                <MDBCol col="4">
                    <MDBCard text="center" class="bg-opacity-25">
                        <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Assigned Agents</h5></MDBCardHeader>
                        <MDBCardBody class="p-2">
                            <MDBCardText><h3>{{ dataset.data?.assigned_licenses.length }}</h3></MDBCardText>
                            <MDBListGroup flush>
                                <MDBListGroupItem v-for="agent in dataset.data?.assigned_licenses">{{ agent.user_id }} - {{ agent.name }}</MDBListGroupItem>
                            </MDBListGroup>
                        </MDBCardBody>
                    </MDBCard>
                </MDBCol>
                <MDBCol col="4">
                    <MDBCard text="center" class="bg-opacity-25">
                        <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Logged In Agents</h5></MDBCardHeader>
                        <MDBCardBody class="p-2">
                            <MDBCardText><h3>{{ dataset.data?.active_agents.length }}</h3></MDBCardText>
                            <MDBListGroup flush>
                                <MDBListGroupItem v-for="agent in dataset.data?.active_agents">{{ agent.user_id }} - {{ agent.name }}</MDBListGroupItem>
                            </MDBListGroup>
                        </MDBCardBody>
                    </MDBCard>
                </MDBCol>
            </MDBRow>
        </template>
    </AsyncPage>
</template>

<script setup>
import {computed, ref, watch} from 'vue';
import {MDBAlert, MDBBtn, MDBCard, MDBCardBody, MDBCardHeader, MDBCardText, MDBCol, MDBListGroup, MDBListGroupItem, MDBRow, MDBSpinner} from "mdb-vue-ui-kit";
import {DateTime} from "luxon";
import {toast} from 'vue3-toastify';

import AsyncPage from "@/components/AsyncPage.vue";
import SmartSelect from '@/components/SmartSelect.vue';
import apiClient from "@/http";
import {differenceBy, differenceWith} from "lodash";

const isLoading = ref(false);
const isSending = ref(false);
const dataset = ref({});
const agents = ref([]);
const agent_ids = ref([])

const assignLicense = () => {
    const newLicenses = agent_ids.value.map((agent_id, index) => {
        const newAgent = availableAgents.value.find(a => a?.value === agent_id);
        if (!newAgent) return false;

        return {
            from: dataset.value.data.available_licenses[index],
            to: {
                user_id: newAgent.value,
                name: newAgent.text,
            },
        }
    });

    apiClient.post('reports/license-swap/email', {licenses: newLicenses})
        .then(({data}) => {
            toast.success("The license swap email is being sent");
        }).catch((error) => {
    }).finally(() => {
        isSending.value = false;
    });
}

const getValues = (refresh) => {
    isLoading.value = true;

    apiClient.get('options/dialer_agents')
        .then(({data}) => {
            agents.value = data;
        }).catch(error => {
    }).finally(() => {
        isLoading.value = false;
    });

    apiClient.get('reports/license-swap', {params: {refresh: refresh}})
        .then(({data}) => {
            dataset.value = data;
        }).catch(error => {
    }).finally(() => {
        isLoading.value = false;
    });
};

const updatedDate = computed(() => {
    if (!dataset.value || !dataset.value.updated_at) {
        return '';
    }
    let date = DateTime.fromISO(dataset.value.updated_at);
    return date.toFormat('L/d/y H:mm:ss');
});

const hasDuplicates = computed(() => {
    let filtered = agent_ids.value.filter((agent_id) => {
        return agent_id !== ''
    });
    return filtered.length && filtered.length !== new Set(filtered).size;
});

const availableAgents = computed(() =>
    differenceWith(agents.value, dataset.value.data.assigned_licenses, (obj1, obj2) => {
        return obj1.value == obj2.user_id
    })
);

getValues(false);
</script>

<style lang="scss">
li.ripple-surface {
    overflow: unset;
}
</style>
