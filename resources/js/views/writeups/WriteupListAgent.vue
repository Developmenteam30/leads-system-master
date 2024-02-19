<template>
    <WriteupList
        label=""
        agent-type="agents"
        :agent-id="agentId"
        :ajax-url="`writeups/agent/${agentId}`"
        :show-filters="false"
        :show-add-button="showAddButton"
        :ajax-payload="{ actions: 1 }"
    />
</template>

<script setup>
import WriteupList from "@/views/writeups/WriteupList.vue";

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
</script>
