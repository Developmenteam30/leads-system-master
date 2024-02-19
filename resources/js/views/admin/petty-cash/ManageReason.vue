<template>
    <MDBModal
        id="reasonModal"
        tabindex="-1"
        labelledby="reasonModalLabel"
        v-model="showModalLocal"
        class="modal-input-spacing"
        staticBackdrop
    >
        <MDBModalHeader>
            <MDBModalTitle id="reasonModalLabel">{{ modalTitle }}</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <Suspense>
                <template #default>
                    <div>
                        <MDBInput label="Reason" v-model="modalValuesLocal.reason"/>
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
    MDBBtn,
    MDBInput,
    MDBModal,
    MDBModalBody,
    MDBModalFooter,
    MDBModalHeader,
    MDBModalTitle,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import apiClient from "@/http";
import {ref, computed} from 'vue';

const props = defineProps({
    title: String,
    showModal: Boolean,
    modalValues: Object,
});

const emit = defineEmits([
    'update:showModal',
    'update:modalValues',
    'update:id',
]);

const isSaving = ref(false);

const modalTitle = computed(() => {
    return (props.modalValues.id ? 'Edit' : 'Add') + ' Reason';
})

const save = () => {
    isSaving.value = true;

    const payload = {
        ...modalValuesLocal.value,
    };

    if (modalValuesLocal.value.id) {
        apiClient.patch(`petty-cash-reasons/manage/${modalValuesLocal.value.id}`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
                if (data.id) {
                    emit('update:id', data.id);
                }
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    } else {
        apiClient.post(`petty-cash-reasons/manage`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
                if (data.id) {
                    emit('update:id', data.id);
                }
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    }
}

const showModalLocal = useModelWrapper(props, emit, 'showModal');
const modalValuesLocal = useModelWrapper(props, emit, 'modalValues');
</script>
<style lang="scss">
</style>
