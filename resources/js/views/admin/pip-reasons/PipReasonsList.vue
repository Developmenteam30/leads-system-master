<template>
    <AbstractList
        ajax-url="pip-reasons/manage"
        title-singular="PIP Reason"
        title-plural="PIP Reasons"
        :form="form"
        @update:form="(value) => form = value"
    >
        <template v-slot:edit-modal>
            <MDBAlert v-if="form.id" color="warning" static class="mb-4"><i class="fas fa-exclamation-triangle me-3"></i> It is recommended to add a new PIP reason since this existing reason may have already been set on existing PIPs.</MDBAlert>

            <MDBInput label="Reason" v-model="form.reason" :maxlength=255 :required="true"/>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBAlert, MDBInput} from "mdb-vue-ui-kit";
import {ref} from "vue";

const form = ref({
    id: null,
});
</script>
<style lang="scss">
</style>
