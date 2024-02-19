<template>
    <p>{{ fileTypesMessage }}</p>
    <template v-if="isFinished">
        <MDBAlert color="success" static>{{ successMessage }}</MDBAlert>
        <MDBBtn color="primary" @click="resetUpload">Upload Another File</MDBBtn>
    </template>
    <template v-else>
        <MDBDatepicker v-if="showDateField" v-model="fileDate" inline inputToggle label="File Date" format="YYYY-MM-DD" confirmDateOnSelect/>
        <MDBFile v-model="files" @change="newFile" label="File"/>
        <MDBProgress class="mt-2">
            <MDBProgressBar :value="progress"/>
        </MDBProgress>
        <MDBBtn class="mt-2" color="primary" @click="click" :disabled="isClicked || (showDateField ? (fileDate.length === 0 || chunks.length === 0) : (chunks.length === 0))">
            Upload
            <MDBSpinner tag="span" size="sm" v-if="startUpload" class="ms-2"/>
        </MDBBtn>
    </template>
</template>

<script setup>
import {MDBAlert, MDBBtn, MDBDatepicker, MDBFile, MDBProgress, MDBProgressBar, MDBSpinner} from "mdb-vue-ui-kit";
import apiClient from "../http";
import {computed, ref, watch} from "vue";
import {uuid} from "vue-uuid";

const emit = defineEmits([
    "uploaded",
]);

const props = defineProps({
    apiEndpoint: {
        type: String,
        required: true,
    },
    showDateField: {
        type: Boolean,
        default: true,
    },
    successMessage: {
        type: String,
        default: 'File upload complete! The file will be processed in the background and an email will be sent once the job is finished.',
    },
    fileTypesMessage: {
        type: String,
        default: 'Please use CSV, XLS, or XLSX format.',
    },
    ajaxPayload: {
        type: Object,
        required: false,
    },
});

const files = ref([]);
const file = ref(null);
const chunks = ref([]);
const uploaded = ref(0);
const isFinished = ref(false);
const fileDate = ref('');
const startUpload = ref(false);
const uniqueId = ref(uuid.v4());
const isClicked = ref(false);

watch(
    () => chunks,
    (n, o) => {
        if (startUpload.value && n.value.length > 0) {
            upload();
        }
    },
    {deep: true}
);

const progress = computed(() => {
    return file.value && uploaded.value ? Math.floor((uploaded.value * 100) / file.value.size) : 0;
});

const select = (event) => {
    file.value = files.value.item(0);
    createChunks();
};

const click = () => {
    isClicked.value = true;
    upload();
}

const upload = () => {

    // Inspired from: https://pineco.de/chunked-file-upload-with-laravel-and-vue/
    let formData = new FormData;
    for (const property in props.ajaxPayload) {
        formData.set(property, props.ajaxPayload[property])
    }
    formData.set('unique_id', uniqueId.value);
    formData.set('is_last', chunks.value.length === 1);
    formData.set('file', chunks.value[0], file.value.name);
    formData.set('file_date', fileDate.value);

    uploaded.value += chunks.value[0].size;

    apiClient.post(props.apiEndpoint, formData, {
        headers: {
            "Content-Type": "multipart/form-data",
        }
    })
        .then(response => {
            if (chunks.value.length === 1) {
                isFinished.value = true;
                isClicked.value = false;
                emit("uploaded");
            } else {
                startUpload.value = true;
            }
            chunks.value.shift();
        }).catch(error => {
        resetUpload();
    });
};

const resetUpload = () => {
    files.value = [];
    file.value = null;
    chunks.value = [];
    uploaded.value = 0;
    isFinished.value = false;
    startUpload.value = false;
    isClicked.value = false;
    uniqueId.value = uuid.v4();
};

const createChunks = () => {
    chunks.value = [];
    let size = 1048576, chunk_count = Math.ceil(file.value.size / size);

    for (let i = 0; i < chunk_count; i++) {
        chunks.value.push(file.value.slice(
            i * size, Math.min(i * size + size, file.value.size), file.value.type
        ));
    }
};

const newFile = (event) => {
    if (event && event.target && event.target.files && event.target.files.length) {
        resetUpload();
        file.value = event.target.files.item(0);
        createChunks();
    }
};

</script>
