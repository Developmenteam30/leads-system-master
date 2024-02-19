<template>
    <MDBModal
        id="editAgentBulkModal"
        tabindex="-1"
        labelledby="editAgentModalBulkLabel"
        v-model="showModalLocal"
        class="modal-input-spacing"
        staticBackdrop
        size="xl"
    >
        <MDBModalHeader>
            <MDBModalTitle id="editAgentModalBulkLabel">Bulk Edit {{ props.title }}s</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <Suspense>
                <template #default>
                    <div>
                        <MDBAlert color="info" static class="mb-4"><i class="fas fa-info-circle me-3"></i> Fill in the fields you wish to bulk edit. Any fields left blank will not be modified.
                        </MDBAlert>

                        <MDBAlert color="warning" static class="mb-4"><i class="fas fa-exclamation-triangle me-3"></i> You are bulk editing <strong>{{ agentIds.length }}</strong> {{ props.title.toLowerCase() }}s. This operation
                            cannot be undone.
                        </MDBAlert>

                        <SmartSelectAjax v-if="'User' !== title" label="Team" ajax-url="options/dialer_teams" v-model:selected="modalValues.team_id"/>

                        <SmartSelectAjax label="Call Center" ajax-url="options/companies" v-model:selected="modalValues.company_id"/>

                        <MDBDatepicker v-model="modalValues.pip_start_date" inputToggle label="PIP Start Date" format="YYYY-MM-DD" confirmDateOnSelect/>

                        <template v-if="authStore.hasAccessToArea('ACCESS_AREA_MANAGE_ACCESS_DETAILS')">
                            <SmartSelectAjax v-if="'Agent' !== title" label="Access Role" ajax-url="options/access_roles" v-model:selected="modalValues.access_role_id"/>
                        </template>
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
    MDBBtn, MDBDatepicker,
    MDBModal,
    MDBModalBody,
    MDBModalFooter,
    MDBModalHeader,
    MDBModalTitle,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import apiClient from "../../http";
import {ref} from 'vue';
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import {cloneDeep} from "lodash";
import {authStore} from "@/store/auth-store";

const emit = defineEmits([
    "reload",
    "update:showModal",
]);

const props = defineProps({
    title: String,
    showModal: Boolean,
    agentIds: Array,
});

const isSaving = ref(false);
const defaults = ref({
    team_id: '',
    company_id: '',
    access_role_id: '',
    pip_start_date: '',
});

const modalValues = ref(cloneDeep(defaults));

const save = () => {
    isSaving.value = true;

    const payload = {
        agent_ids: props.agentIds,
        ...modalValues.value,
    };

    apiClient.patch(`agents/bulk-manage`, payload)
        .then(({data}) => {
            showModalLocal.value = false;
            modalValues.value = cloneDeep(defaults);
            emit("reload");
        }).catch(error => {
    }).finally(() => {
        isSaving.value = false;
    });
}

const showModalLocal = useModelWrapper(props, emit, 'showModal');
</script>
<style lang="scss">
</style>
