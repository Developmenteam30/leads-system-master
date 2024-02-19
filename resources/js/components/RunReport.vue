<template>
    <FilterColumn>
        <MDBBtn color="primary" @click="emit('clicked')" :disabled="isLoading">Run Report
            <MDBSpinner tag="span" size="sm" v-if="isLoading" class="ms-2"/>
        </MDBBtn>
    </FilterColumn>
</template>

<script setup>
import {
    MDBBtn,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import FilterColumn from "./FilterColumn.vue";

const emit = defineEmits([
    "clicked",
]);

const props = defineProps({
    isLoading: {
        type: Boolean,
        default: false,
    },
});
</script>
<style lang="scss">
</style>
