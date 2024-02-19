<template>
    <template v-if="values && values.holiday">
        <MDBAlert color="warning" static class="mt-3"><i class="fas fa-exclamation-triangle me-3"></i> This report period contains a holiday. Please make note of the holiday columns.</MDBAlert>
    </template>
    <template v-if="values && values.rollover">
        <MDBAlert color="warning" static class="mt-3"><i class="fas fa-exclamation-triangle me-3"></i> This report period contains a Friday or Saturday holiday. Verify that next Monday's hours are
            loaded and accurate to ensure proper holiday calculations for this week.
        </MDBAlert>
    </template>
</template>

<script setup>
import {
    MDBAlert,
} from "mdb-vue-ui-kit";
import apiClient from "../http";
import {computed, onMounted, ref, watch} from "vue";
import {cloneDeep, debounce, isEqual} from "lodash";

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

const values = ref({});

watch(() => cloneDeep(props.payload), (selection, prevSelection) => {
    if (!isEqual(selection, prevSelection)) {
        debouncedGetValues()
    }
});

const getValues = async () => {
    apiClient.get('reports/payroll/holiday-warnings', {
        params: {
            ...props.payloadsync,
            ...props.payload,
        }
    })
        .then(({data}) => {
            values.value = data;
        }).catch(error => {
    }).finally(() => {
    });
};

const payloadLocal = computed(() => {
    return {
        start_date: props.payload.start_date || '',
        end_date: props.payload.end_date || '',
    }
});

const debouncedGetValues = debounce(getValues, 500);

await getValues();

defineExpose({
    getValues,
});

</script>
<style lang="scss">
</style>
