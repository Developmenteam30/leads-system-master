<template>
    <template v-if="payrollExceptions">
        <router-link :to="{name:'reports-payroll-exceptions', query: payloadLocal}">
            <MDBAlert color="warning" static class="mt-3"><i class="fas fa-exclamation-triangle me-3"></i> Exceptions exist for this report period.</MDBAlert>
        </router-link>
    </template>
</template>

<script setup>
import {
    MDBAlert,
} from "mdb-vue-ui-kit";
import apiClient from "../http";
import {computed, onMounted, ref, watch} from "vue";
import {cloneDeep, debounce, isEqual} from "lodash";
import {useRoute} from "vue-router";

const route = useRoute();

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

const payrollExceptions = ref(false);

watch(() => cloneDeep(props.payload), (selection, prevSelection) => {
    if (!isEqual(selection, prevSelection)) {
        debouncedGetValues()
    }
});

const getValues = async () => {
    apiClient.get('reports/payroll/exceptions', {
        params: {
            ...props.payloadsync,
            ...props.payload,
        }
    })
        .then(({data}) => {
            payrollExceptions.value = (data && data.rows && data.rows.length);
        }).catch(error => {
    }).finally(() => {
    });
};

const payloadLocal = computed(() => {
    return {
        start_date: props.payloadsync.start_date || '',
        end_date: props.payloadsync.end_date || '',
        return_route: route.name,
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
