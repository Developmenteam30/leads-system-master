<template>
    <CustomDatatableAjax
        :ajax-url="ajaxUrl"
        :ajax-payload="ajaxPayload"
        :entries="10000"
        striped
        fixedHeader
        class="mt-1"
        clickableRows
        :pagination="pagination"
        :auto-height="false"
        @row-click-values="showEvaluation"
    />
</template>

<script setup>
import CustomDatatableAjax from './CustomDatatableAjax.vue';
import router from "@/routes";

const props = defineProps({
    ajaxUrl: {
        type: String,
        required: true
    },
    ajaxPayload: {
        type: Object,
    },
    pagination: {
        type: Boolean,
        default: false
    }
});

const showEvaluation = (values) => {
    router.push({
        name: 'reports-performance-tracker', query: {
            evaluation_id: values.evaluation_id,
            agent_id: values.agent_id,
            start_date: values.start_date,
            end_date: values.end_date,
        }
    })
};
</script>
