<template>
    <MDBModal
        id="locationModal"
        tabindex="-1"
        labelledby="locationModalLabel"
        v-model="showModalLocal"
        class="modal-input-spacing"
        staticBackdrop
    >
        <MDBModalHeader>
            <MDBModalTitle id="locationModalLabel">{{ modalTitle }}</MDBModalTitle>
        </MDBModalHeader>
        <MDBModalBody>
            <Suspense>
                <template #default>
                    <div>
                        <MDBInput label="Location" v-model="modalValuesLocal.location"/>
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
    'update:id'
]);

const isSaving = ref(false);

const modalTitle = computed(() => {
    return (props.modalValues.id ? 'Edit' : 'Add') + ' Location';
})

const save = () => {
    isSaving.value = true;

    const payload = {
        ...modalValuesLocal.value,
    };

    if (modalValuesLocal.value.id) {
        apiClient.patch(`petty-cash-locations/manage/${modalValuesLocal.value.id}`, payload)
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
        apiClient.post(`petty-cash-locations/manage`, payload)
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
