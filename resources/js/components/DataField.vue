<template>
    <p><label>{{ label }}</label>: {{ value }}</p>
</template>

<script setup>

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number, null],
        required: true,
    },
});
</script>
<style lang="scss" scoped>
label {
    font-weight: bold;
}
</style>
