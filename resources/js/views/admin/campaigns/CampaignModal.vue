<template>
    <MDBModal
        id="editModal"
        tabindex="-1"
        labelledby="editModalLabel"
        v-model="showModalLocal"
        class="modal-input-spacing"
        staticBackdrop
        size="xl"
    >
        <MDBModalHeader>
            <MDBModalTitle id="editModalLabel">{{ modalTitle }}</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <Suspense>
                <template #default>
                    <div>
                        <MDBInput label="Campaign Name" v-model="modalValuesLocal.name" :maxlength=255 :required="true"/>

                        <template v-if="modalValuesLocal.id">
                            <h5 class="mt-3">Call Center Defaults</h5>
                            <MDBBtn color="primary" size="sm" @click="showDefaultModal(null)">Add new default</MDBBtn>
                            <CustomDatatableAjax
                                :ajax-url="`campaigns/companies/${modalValuesLocal.id}`"
                                :entries="10000"
                                striped
                                fixedHeader
                                class="mt-1"
                                :pagination="false"
                                clickableRows
                                :key="defaultKey"
                                @row-click-values="showDefaultModal"
                                :auto-height="false"
                            />
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
            <MDBBtn outline="dark" @click="cancel">Cancel</MDBBtn>
            <MDBBtn color="primary" @click="save">Save changes
                <MDBSpinner tag="span" size="sm" v-if="isSaving" class="ms-2"/>
            </MDBBtn>
        </MDBModalFooter>
    </MDBModal>
    <CampaignDefaultModal
        :showModal="defaultModal"
        @update:showModal="updateDefaultModal"
        :modalValues="defaultModalValues"
    >
    </CampaignDefaultModal>
</template>

<script setup>
import {useModelWrapper} from "@/modelWrapper";
import {
    MDBBtn,
    MDBInput,
    MDBModal,
    MDBModalBody,
    MDBModalFooter,
    MDBModalHeader,
    MDBModalTitle,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import apiClient from "../../../http";
import {ref, computed, onMounted, nextTick} from 'vue';
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import CampaignDefaultModal from "@/views/admin/campaigns/CampaignDefaultModal.vue";

const emit = defineEmits([
    "reload",
    "update:showModal",
]);

const props = defineProps({
    showModal: Boolean,
    modalValues: Object,
});

const isSaving = ref(false);
const defaultKey = ref(0);
const defaultModal = ref(false);
const defaultModalValues = ref({});

const modalTitle = computed(() => {
    return props.modalValues.id ? `Edit Campaign - ID ${props.modalValues.id}` : `Add a Campaign`;
})

const save = () => {
    isSaving.value = true;

    const payload = {
        ...modalValuesLocal.value,
    };

    if (modalValuesLocal.value.id) {
        apiClient.patch(`campaigns/manage/${modalValuesLocal.value.id}`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
                emit("reload");
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    } else {
        apiClient.post(`campaigns/manage`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
                emit("reload");
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    }
};

const cancel = () => {
    showModalLocal.value = false;
    emit("reload");
}

const showDefaultModal = (values) => {
    if (values !== null) {
        defaultModalValues.value = {
            ...values,
            error: '',
        };
    } else {
        defaultModalValues.value = {
            id: null,
            campaign_id: modalValuesLocal.value.id,
            error: '',
        };
    }
    defaultModal.value = true;
}

const updateDefaultModal = (value) => {
    defaultModal.value = value;
    if (value === false) {
        nextTick(() => {
            defaultKey.value++;
        });
    }
}


const showModalLocal = useModelWrapper(props, emit, 'showModal');
const modalValuesLocal = useModelWrapper(props, emit, 'modalValues');
</script>
<style lang="scss">
</style>
