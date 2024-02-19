<template>
    <AbstractList
        ajax-url="holidays/manage"
        title-singular="Holiday"
        title-plural="Holidays"
        :form="form"
        @update:form="(value) => form = value"
    >
        <template v-slot:edit-modal>
            <MDBAlert v-if="form.id" color="warning" static class="mb-4"><i class="fas fa-exclamation-triangle me-3"></i> It is recommended to add a new holiday since modifying an existing holiday date will affect past payroll numbers.</MDBAlert>

            <MDBDatepicker v-model="form.holiday" inline inputToggle label="Date" format="YYYY-MM-DD" confirmDateOnSelect :required="true"/>

            <MDBInput label="Name" v-model="form.name" :maxlength=255 :required="true"/>

            <MDBInput label="Multiplier (1.5 or 2.0)" v-model="form.multiplier" :maxlength=255 :required="true"/>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBAlert, MDBDatepicker, MDBInput} from "mdb-vue-ui-kit";
import {ref} from "vue";

const form = ref({
    id: null,
});
</script>
<style lang="scss">
</style>
