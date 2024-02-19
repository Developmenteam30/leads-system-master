<template>
    <MDBRow class="mt-3">
        <DispositionStatusCard :hours-status="counts.hours.mon" :dispo-status="counts.dispos.mon" :is-loading="isLoading" day="Mon"></DispositionStatusCard>
        <DispositionStatusCard :hours-status="counts.hours.tue" :dispo-status="counts.dispos.tue" :is-loading="isLoading" day="Tue"></DispositionStatusCard>
        <DispositionStatusCard :hours-status="counts.hours.wed" :dispo-status="counts.dispos.wed" :is-loading="isLoading" day="Wed"></DispositionStatusCard>
        <DispositionStatusCard :hours-status="counts.hours.thu" :dispo-status="counts.dispos.thu" :is-loading="isLoading" day="Thu"></DispositionStatusCard>
        <DispositionStatusCard :hours-status="counts.hours.fri" :dispo-status="counts.dispos.fri" :is-loading="isLoading" day="Fri"></DispositionStatusCard>
        <DispositionStatusCard :hours-status="counts.hours.sat" :dispo-status="counts.dispos.sat" :is-loading="isLoading" day="Sat"></DispositionStatusCard>
        <DispositionStatusCard :hours-status="counts.hours.sun" :dispo-status="counts.dispos.sun" :is-loading="isLoading" day="Sun"></DispositionStatusCard>
    </MDBRow>
</template>

<script setup>
import {
    MDBRow, MDBSpinner,
} from "mdb-vue-ui-kit";
import apiClient from "../http";
import {onMounted, ref, watch} from "vue";
import {cloneDeep, debounce, isEqual} from "lodash";
import DispositionStatusCard from "@/components/DispositionStatusCard.vue";

const isLoading = ref(false);

const props = defineProps({
    payload: {
        type: Object,
        required: false,
    },
    payloadsync: {
        type: Object,
        required: false,
    }
});

const counts = ref({
    dispos: {
        mon: null,
        tue: null,
        wed: null,
        thu: null,
        fri: null,
        sat: null,
        sun: null,
    },
    hours: {
        mon: null,
        tue: null,
        wed: null,
        thu: null,
        fri: null,
        sat: null,
        sun: null,
    },
});

watch(() => cloneDeep(props.payload), (selection, prevSelection) => {
    if (!isEqual(selection, prevSelection)) {
        debouncedGetValues()
    }
});

const getValues = async () => {
    isLoading.value = true;

    await apiClient.get('reports/database-status', {
        params: {
            ...props.payloadsync,
            ...props.payload,
        }
    })
        .then(({data}) => {
            counts.value = data;
        }).finally(() => {
            isLoading.value = false;
        });
};

const debouncedGetValues = debounce(getValues, 500);

await getValues();

defineExpose({
    getValues,
});

</script>
<style lang="scss">
</style>
