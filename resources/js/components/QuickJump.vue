<template>
    <FilterColumn>
        <SmartSelectAjax label="Quick Jump" :ajax-url="'quick-jump/' + props.interval" v-model:selected="quickJump" :visibleOptions="10" :disabled="disabled"/>
    </FilterColumn>
    <FilterColumn>
        <MDBDatepicker v-model="startDateLocal" inline inputToggle label="Start Date" format="YYYY-MM-DD" :min="quickJumpDateMin" :max="quickJumpDateMax" confirmDateOnSelect :disabled="disabled"/>
    </FilterColumn>
    <FilterColumn>
        <MDBDatepicker v-model="endDateLocal" inline inputToggle label="End Date" format="YYYY-MM-DD" :min="quickJumpDateMin" :max="quickJumpDateMax" confirmDateOnSelect :disabled="disabled"/>
    </FilterColumn>
</template>

<script setup>
import {
    MDBDatepicker,
} from "mdb-vue-ui-kit";
import {computed, ref, watch} from "vue";
import FilterColumn from "./FilterColumn.vue";
import {DateTime} from "luxon";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";

const emit = defineEmits([
    "update",
]);

const props = defineProps({
    constrain: {
        type: Boolean,
        default: false,
    },
    interval: {
        type: String,
        default: "default",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    startDate: {
        type: String,
    },
    endDate: {
        type: String,
    }
});

const startDateLocal = ref(props.startDate ? props.startDate : ('default' === props.interval ? '' : DateTime.now().startOf(props.interval).toISODate()));
const endDateLocal = ref(props.endDate ? props.endDate : ('default' === props.interval ? '' : DateTime.now().endOf(props.interval).toISODate()));
const quickJump = ref(startDateLocal.value + '|' + endDateLocal.value);

const quickJumpDateMin = computed(() => {
    if (true !== props.constrain) {
        return "";
    }
    const dateParts = quickJump.value.split('|');
    return dateParts[0];
})

const quickJumpDateMax = computed(() => {
    if (true !== props.constrain) {
        return "";
    }
    const dateParts = quickJump.value.split('|');
    return dateParts[1];
})

watch(quickJump, (newValue) => {
    if (newValue && newValue.length) {
        const dateParts = newValue.split('|');
        startDateLocal.value = dateParts[0];
        endDateLocal.value = dateParts[1];
    } else {
        startDateLocal.value = '';
        endDateLocal.value = '';
    }
    emit("update", {startDate: startDateLocal.value, endDate: endDateLocal.value});
})

watch(startDateLocal, (newValue) => {
    emit("update", {startDate: startDateLocal.value, endDate: endDateLocal.value});
})

watch(endDateLocal, (newValue) => {
    emit("update", {startDate: startDateLocal.value, endDate: endDateLocal.value});
})
</script>
<style lang="scss">
</style>
