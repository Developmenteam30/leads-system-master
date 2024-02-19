<template>
    <SmartSelect
        v-bind="{...$props, ...$attrs}"
        v-model:options="options"
        v-model:selected="selectedLocal"/>
</template>

<script setup>
import apiClient from "../http";
import {ref} from "vue";
import SmartSelect from "./SmartSelect.vue";
import {useModelWrapper} from "@/modelWrapper";

const emit = defineEmits([
    "update:selected",
]);

const props = defineProps({
    ajaxPayload: {
        type: Object,
        required: false,
    },
    ajaxUrl: {
        type: String,
        required: true,
    },
    clearButton: {
        type: Boolean,
        default: true,
    },
    filter: {
        type: Boolean,
        default: true,
    },
    label: {
        type: String,
    },
    preselect: {
        type: Boolean,
        default: false,
    },
    selected: [String, Array, Number],
});

const options = ref([]);
const selectedLocal = useModelWrapper(props, emit, 'selected');

const response = await apiClient.get(props.ajaxUrl, {params: props.ajaxPayload});
options.value = response.data;
</script>
<style lang="scss">
</style>
