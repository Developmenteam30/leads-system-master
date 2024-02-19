<template>
    <AbstractList
        :ajax-url="`documents`"
        title-singular="Document"
        title-plural="Documents"
        :form="form"
        :filter-form="filterForm"
        :exportable="false"
        :key="key"
        @reload="key++"
        :show-edit-modal-footer="false"
        :has-actions="true"
        @update:form="(value) => form = value"
        @update:loading="(value) => isLoading = value"
    >
        <template v-slot:filters>
            <MDBContainer class="mt-3 p-0 m-0">
                <FilterRow>
                    <FilterColumn>
                        <SmartSelectAjax label="Document Type" ajax-url="options/document-types" v-model:selected="filterForm.document_type_ids" multiple/>
                    </FilterColumn>
                    <FilterColumn>
                        <MDBInput v-model="filterForm.agent_search" :label="`Search by agent name`" :readonly="isLoading"/>
                    </FilterColumn>
                    <FilterColumn>
                        <MDBInput v-model="filterForm.title_search" :label="`Search by document title`" :readonly="isLoading"/>
                    </FilterColumn>
                    <FilterColumn :last="true">
                        <MDBCheckbox label="Include archived" v-model="filterForm.include_archived" :disabled="isLoading"/>
                    </FilterColumn>
                </FilterRow>
            </MDBContainer>
        </template>
        <template v-slot:edit-modal>
            <SmartSelectAjax label="Agent" ajax-url="options/dialer_agents" v-model:selected="form.agent_id" :show-required="true"/>

            <template v-if="form.agent_id">
                <SmartSelectAjax label="Document Type" ajax-url="options/document-types/agent" v-model:selected="form.document_type_id" :show-required="true"/>

                <file-uploader
                    :showDateField="false"
                    :api-endpoint="`documents/agent/${form.agent_id}`"
                    :ajax-payload="{ ...form }"
                    @uploaded="key++"
                    success-message="Your document has been uploaded."
                    file-types-message="Accepted file types: Text File, Word Document, Excel Document, PDF, Image File (PNG, JPEG, GIF, BMP, TIFF)"
                ></file-uploader>
            </template>
        </template>
        <template v-slot:view-modal>
            <DataField
                label="Agent Name"
                v-if="form.documentable"
                :value="form.documentable.agent_name"
            >
            </DataField>

            <DataField
                label="Document Type"
                v-if="form.document_type"
                :value="form.document_type.name"
            >
            </DataField>

            <DataField
                label="Document Title"
                v-if="form.title"
                :value="form.title"
            >
            </DataField>
        </template>
    </AbstractList>
</template>
`
<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {ref} from "vue";
import FileUploader from "@/components/FileUploader.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import {MDBCheckbox, MDBContainer, MDBInput} from "mdb-vue-ui-kit";
import FilterRow from "@/components/FilterRow.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import DataField from "@/components/DataField.vue";

const form = ref({
    id: null,
    agent_id: '',
    document_type_id: '',
})

const isLoading = ref(false);

const filterForm = ref({
    document_type_ids: '',
    agent_search: '',
    title_search: '',
    include_archived: false,
});

const key = ref(0);
</script>
<style lang="scss">
</style>
