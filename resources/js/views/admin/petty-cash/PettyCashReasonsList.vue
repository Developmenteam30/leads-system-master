<template>
    <AbstractList
        ajax-url="petty-cash-reasons/manage"
        title-singular="Petty Cash Reason"
        title-plural="Petty Cash Reasons"
        :form="form"
        :filter-form="filterForm"
        @update:form="(value) => form = value"
        @update:loading="(value) => isLoading = value"
    >
        <template v-slot:filters>
            <MDBContainer class="mt-3 p-0 m-0">
                <FilterRow>
                    <FilterColumn>
                        <MDBInput v-model="filterForm.search" :label="`Search by name`" :readonly="isLoading"/>
                    </FilterColumn>
                    <FilterColumn :last="true">
                        <MDBCheckbox label="Include archived" v-model="filterForm.include_archived" :disabled="isLoading"/>
                    </FilterColumn>
                </FilterRow>
            </MDBContainer>
        </template>
        <template v-slot:edit-modal>
            <MDBAlert v-if="form.id" color="warning" static class="mb-4"><i class="fas fa-exclamation-triangle me-3"></i> Changing this value will update the reason for all Petty Cash entries already on file.</MDBAlert>

            <MDBInput label="Reason" v-model="form.reason" :maxlength=255 :required="true"/>

            <MDBCheckbox v-if="form.id" label="Archived" v-model="form.isArchived"/>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBAlert, MDBCheckbox, MDBContainer, MDBInput} from "mdb-vue-ui-kit";
import {ref} from "vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";

const isLoading = ref(false);
const form = ref({
    id: null,
});

const filterForm = ref({
    search: '',
    include_archived: false,
});
</script>
<style lang="scss">
</style>
