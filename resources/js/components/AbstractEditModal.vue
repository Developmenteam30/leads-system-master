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
                <MDBModalTitle :id="`label-${modalId}`">{{ modalTitle }}</MDBModalTitle>
            </MDBModalHeader>
            <MDBModalBody>
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
            <MDBModalFooter v-if="showEditModalFooter">
                <MDBBtn outline="dark" @click="cancel">Cancel</MDBBtn>
                <MDBBtn color="primary" @click="save">Save changes
                    <MDBSpinner tag="span" size="sm" v-if="isSaving" class="ms-2"/>
                </MDBBtn>
            </MDBModalFooter>
        </template>
    </MDBModal>
</template>

<script setup>
import {useModelWrapper} from "@/modelWrapper";
import {
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
    showEditModalFooter: {
        type: Boolean,
        default: true,
    },
});

const isSaving = ref(false);

const modalTitle = computed(() => {
    return props.form.id ? `Edit ${props.titleSingular} - ID ${props.form.id}` : `Add ${props.titleSingular}`;
})

const modalId = uuid.v1();

const save = () => {
    isSaving.value = true;

    const payload = {
        ...props.form,
    };

    if (props.form.id) {
        apiClient.patch(`${props.ajaxUrl}/${props.form.id}`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
                emit("reload");
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    } else {
        apiClient.post(props.ajaxUrl, payload)
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

const showModalLocal = useModelWrapper(props, emit, 'showModal');
</script>
<style lang="scss">
</style>
