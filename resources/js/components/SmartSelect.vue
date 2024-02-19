<template>
    <div :class="className">
        <MDBSelect
            v-bind="{...$props, ...$attrs}"
            v-model:options="filteredOptions"
            v-model:selected="selectedLocal"
        />
    </div>
</template>

<script setup>
import {MDBSelect} from "mdb-vue-ui-kit";
import {computed} from "vue";
import {useModelWrapper} from "@/modelWrapper";

const emit = defineEmits([
    "update:selected",
]);

const props = defineProps({
    options: {
        type: Array,
        required: true,
    },
    selected: [String, Array, Number],
    multiple: Boolean,
    showRequired: Boolean,
});

const filteredOptions = computed(() => {
    let tempSelected = props.selected;
    if (props.multiple && props.selected && !Array.isArray(props.selected)) {
        tempSelected = props.selected.split(",").map(item => parseInt(item));
    }

    if (Array.isArray(tempSelected)) {
        return props.options.map(item => ({
            ...item,
            selected: tempSelected.includes(item.value),
        }));
    } else {
        return props.options.map(item => ({
            ...item,
            selected: item.value === props.selected,
        }));
    }
});

const className = computed(() => {
    return [
        "smart-select-wrapper",
        props.showRequired ? "smart-select-required" : "",
    ];
});

const selectedLocal = useModelWrapper(props, emit, 'selected');

</script>
