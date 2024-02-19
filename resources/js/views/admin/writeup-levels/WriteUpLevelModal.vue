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
                        <MDBInput label="Level" v-model="modalValuesLocal.name" :maxlength=255 :required="true"/>
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
import {ref, computed, onMounted} from 'vue';

const emit = defineEmits([
    "reload",
    "update:showModal",
]);

const props = defineProps({
    showModal: Boolean,
    modalValues: Object,
});

const isSaving = ref(false);

const modalTitle = computed(() => {
    return props.modalValues.id ? `Edit Write-Up Level - ID ${props.modalValues.id}` : `Add a Write-Up Level`;
})

const save = () => {
    isSaving.value = true;

    const payload = {
        ...modalValuesLocal.value,
    };

    if (modalValuesLocal.value.id) {
        apiClient.patch(`writeup-levels/manage/${modalValuesLocal.value.id}`, payload)
            .then(({data}) => {
                showModalLocal.value = false;
                emit("reload");
            }).catch(error => {
        }).finally(() => {
            isSaving.value = false;
        });
    } else {
        apiClient.post(`writeup-levels/manage`, payload)
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
const modalValuesLocal = useModelWrapper(props, emit, 'modalValues');
</script>
<style lang="scss">
</style>
