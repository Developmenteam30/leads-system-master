<template>
    <LeaveRequestList
        ajax-url="leave-requests"
        @reload="reload"
    />
</template>

<script setup>
import LeaveRequestList from "@/views/leave-requests/LeaveRequestList.vue";

const emit = defineEmits(["reload"]);

const reload = () => emit("reload");
</script>
