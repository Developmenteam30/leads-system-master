<template>
    <component :is="tag" :class="className">
        <slot/>
    </component>
</template>

<script lang="ts">
export default {
    name: "FilterRow",
};
</script>

<script setup lang="ts">
import {computed} from "vue";

const props = defineProps({
    tag: {
        type: String,
        default: "div",
    },
});

const className = computed(() => {
    return [
        "d-sm-flex",
        "flex-row",
        "align-items-center",
    ];
});

</script>
