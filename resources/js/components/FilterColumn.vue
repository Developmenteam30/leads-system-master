<template>
    <component :is="tag" :class="className" :style="style">
        <slot/>
    </component>
</template>

<script lang="ts">
export default {
    name: "FilterColumn",
};
</script>

<script setup lang="ts">
import {computed} from "vue";

const props = defineProps({
    tag: {
        type: String,
        default: "div",
    },
    last: {
        type: Boolean,
        default: false,
    },
    grow: {
        type: String,
        default: "",
    },
    maxWidth: {
        type: String,
        default: "none",
    }
});

const className = computed(() => {
    return [
        "mt-2",
        "mt-sm-0",
        props.grow ? "flex-grow-" + props.grow : "",
        props.last ? "" : "me-2",
    ];
});

const style = computed(() => {
    return {
        maxWidth: props.maxWidth,
    }
});
</script>
<style lang="scss" scoped>
</style>
