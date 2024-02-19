<template>
    <AbstractList
        ajax-url="holiday-lists/manage"
        title-singular="Holiday List"
        title-plural="Holiday Lists"
        :form="form"
        @update:form="(value) => form = value"
        :defaults="defaults"
    >
        <template v-slot:edit-modal>
            <MDBInput label="Name" v-model="form.name" :maxlength=255 :required="true"/>

            <OptionChecklist
                ajax-url="options/holidays"
                :selected="form.holidays_ids"
                @update:selected="(value) => form.holidays_ids = value"
                :show-check-all="true"
                :show-clear-all="true"
                header="Holidays"
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
    holidays_ids: [],
});

const defaults = cloneDeep(form);
</script>
<style lang="scss">
</style>
