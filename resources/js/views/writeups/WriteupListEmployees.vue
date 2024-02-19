<template>
    <WriteupList
        label="Employee"
        agent-type="employees"
        ajax-url="writeups/manage"
    />
</template>

<script setup>
import WriteupList from "@/views/writeups/WriteupList.vue";
</script>
