<template>
    <AbstractList
        ajax-url="roles/manage"
        title-singular="Access Roles"
        title-plural="Access Role"
        :form="form"
        @update:form="(value) => form = value"
        :defaults="defaults"
    >
        <template v-slot:edit-modal>
            <MDBInput label="Role Name" v-model="form.name" :maxlength=255 :required="true"/>

            <MDBInput label="Abbreviation (up to 5 characters)" v-model="form.abbreviation" :maxlength=5 />

            <OptionChecklist
                ajax-url="options/access_areas"
                :selected="form.accessAreasList"
                @update:selected="(value) => form.accessAreasList = value"
                :show-check-all="true"
                :show-clear-all="true"
                header="Access Areas"
                description-field="description"
            />

            <OptionChecklist
                ajax-url="options/notification_types"
                :selected="form.notificationTypesList"
                @update:selected="(value) => form.notificationTypesList = value"
                :show-check-all="true"
                :show-clear-all="true"
                header="Email Notifications"
                description-field="description"
            />
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBInput} from "mdb-vue-ui-kit";
import {ref} from "vue";
import OptionChecklist from "@/components/OptionChecklist.vue";
import {cloneDeep} from "lodash";

const form = ref({
    id: null,
    name: null,
    accessAreasList: [],
    notificationTypesList: [],
});

const defaults = cloneDeep(form);
</script>
<style lang="scss">
</style>
