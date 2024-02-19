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
                <MDBModalTitle :id="`label-${modalId}`">{{ titleSingular }} History</MDBModalTitle>
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
            <MDBModalFooter>
                <MDBBtn outline="dark" @click="showModalLocal = false">Close</MDBBtn>
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
import {uuid} from 'vue-uuid'
import WaitingSpinner from "@/components/WaitingSpinner.vue";

const emit = defineEmits([
    "update:showModal",
]);

const props = defineProps({
    showModal: Boolean,
    titleSingular: String,
    form: {
        type: Object,
    },
    isFetching: {
        type: Boolean,
        default: false,
    },
});

const modalId = uuid.v1();

const showModalLocal = useModelWrapper(props, emit, 'showModal');
</script>
<style lang="scss">
</style>
