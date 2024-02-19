<template>
    <MDBModal
        :id="modalId"
        tabindex="-1"
        :labelledby="`label-${modalId}`"
        v-model="showModalLocal"
        class="modal-input-spacing"
        staticBackdrop
        size="xl"
        centered
    >
        <WaitingSpinner v-if="isFetching"></WaitingSpinner>
        <template v-else>
            <MDBModalHeader>
                <MDBModalTitle :id="`label-${modalId}`">Undelete {{ titleSingular }} - ID {{ props.form.id }}</MDBModalTitle>
            </MDBModalHeader>
            <MDBModalBody>
                <MDBAlert color="warning" static><i class="fas fa-exclamation-circle me-3"></i> Are you sure you wish to undelete this record?</MDBAlert>
                <Suspense>
                    <template #default>
                        <slot></slot>
                    </template>
                    <template #fallback>
                        <div class="flex-grow-1 text-center">
                            <MDBSpinner/>
                        </div>
                    </template>
                </Suspense>

            </MDBModalBody>
            <MDBModalFooter>
                <MDBBtn outline="dark" @click="showModalLocal = false">Close</MDBBtn>
                <MDBBtn color="warning" @click="save">Undelete
                    <MDBSpinner tag="span" size="sm" v-if="isSaving" class="ms-2"/>
                </MDBBtn>
            </MDBModalFooter>
        </template>
    </MDBModal>
</template>

<script setup>
import {useModelWrapper} from "@/modelWrapper";
import {
    MDBAlert,
    MDBBtn,
    MDBModal,
    MDBModalBody,
    MDBModalFooter,
    MDBModalHeader,
    MDBModalTitle,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import apiClient from "@/http";
import {ref, computed} from 'vue';
import {uuid} from 'vue-uuid'
import WaitingSpinner from "@/components/WaitingSpinner.vue";

const emit = defineEmits([
    "reload",
    "update:showModal",
]);

const props = defineProps({
    showModal: Boolean,
    titleSingular: String,
    ajaxUrl: {
        type: String,
        required: true,
    },
    form: {
        type: Object,
    },
    isFetching: {
        type: Boolean,
        default: false,
    },
});

const isSaving = ref(false);

const modalId = uuid.v1();

const save = () => {
    isSaving.value = true;

    const payload = {
        ...props.form,
    };

    apiClient.patch(`${props.ajaxUrl}/${props.form.id}/restore`, payload)
        .then(({data}) => {
            showModalLocal.value = false;
            emit("reload");
        }).catch(error => {
    }).finally(() => {
        isSaving.value = false;
    });
};

const showModalLocal = useModelWrapper(props, emit, 'showModal');
</script>
<style lang="scss">
</style>
