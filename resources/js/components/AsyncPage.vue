<template>
    <Suspense>
        <template #default>
            <slot/>
        </template>
        <template #fallback>
            <div class="flex-grow-1 text-center">
                <MDBSpinner/>
            </div>
        </template>
    </Suspense>
</template>
<script setup>
import {MDBSpinner} from "mdb-vue-ui-kit";
</script>
