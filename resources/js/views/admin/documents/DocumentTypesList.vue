<template>
    <AbstractList
        ajax-url="document-types/manage"
        title-singular="Document Type"
        title-plural="Document Types"
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
            <MDBAlert v-if="form.id" color="warning" static class="mb-4"><i class="fas fa-exclamation-triangle me-3"></i> Changing this value will update the reason for all documents already on file.
            </MDBAlert>

            <MDBInput label="Name" v-model="form.name" :maxlength=255 :required="true"/>

            <MDBAlert color="info" static class="mb-4"><i class="fas fa-info-circle me-3"></i> This is a developer option that ties a document type to a database class. See existing entries
                for examples of the correct value. If is not recommending changing this value once set as it may affect existing uploads.
            </MDBAlert>
            <MDBInput label="Database Class" v-model="form.documentable_type" :maxlength=255 :required="true"/>

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
