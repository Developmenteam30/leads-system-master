<template>
    <LeaveRequestList
        :agent-id="agentId"
        :ajax-url="`leave-requests/agent/${agentId}`"
        :show-filters="false"
        :show-add-button="showAddButton"
        :ajax-payload="{ actions: 1 }"
        :embedded="true"
        @reload="reload"
    />
</template>

<script setup>
import LeaveRequestList from "@/views/leave-requests/LeaveRequestList.vue";

const props = defineProps({
    agentId: {
        type: [String, Number, null],
        required: true,
    },
    showAddButton: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["reload"]);

const reload = () => emit("reload");
</script>
