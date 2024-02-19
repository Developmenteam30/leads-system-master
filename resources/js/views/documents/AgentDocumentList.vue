<template>
    <AbstractList
        :ajax-url="`documents/agent/${props.agentId}`"
        title-singular="Document"
        title-plural="Documents"
        :form="form"
        :exportable="false"
        :key="key"
        @reload="key++"
        :show-edit-modal-footer="false"
        :has-actions="true"
        :embedded="embedded"
    >
        <template v-slot:edit-modal>
            <SmartSelectAjax label="Document Type" ajax-url="options/document-types/agent" v-model:selected="form.document_type_id" :show-required="true"/>

            <file-uploader
                :showDateField="false"
                :api-endpoint="`documents/agent/${props.agentId}`"
                :ajax-payload="{ ...form }"
                @uploaded="key++"
                success-message="Your document has been uploaded."
                file-types-message="Accepted file types: Text File, Word Document, Excel Document, PDF, Image File (PNG, JPEG, GIF, BMP, TIFF)"
            ></file-uploader>
        </template>
        <template v-slot:view-modal>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {ref} from "vue";
import FileUploader from "@/components/FileUploader.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";

const props = defineProps({
    agentId: {
        type: [String, Number],
        required: true,
    },
    embedded: {
        type: Boolean,
        default: false,
    },
});

const form = ref({
    id: null,
    document_type_id: '',
})

const key = ref(0);
</script>
<style lang="scss">
</style>
