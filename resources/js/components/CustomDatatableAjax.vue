<template>
    <div>
        <div class="d-flex justify-content-center">
            <div class="flex-grow-1 text-center">
                <MDBSpinner v-if="isLoading"/>
            </div>
            <div v-if="exportable && authStore.hasAccessToArea('ACCESS_AREA_UNIVERSAL_EXPORT')">
                <MDBBtn color="primary" @click="exportData" :disabled="isLoading">Export
                    <MDBSpinner tag="span" size="sm" v-if="isExporting" class="ms-2"/>
                </MDBBtn>
            </div>
        </div>
        <CustomDatatable
            v-bind="{...$props, ...$attrs}"
            :dataset="dataset"
            :loading="isLoading"
            v-if="!isLoading && dataset.columns.length"
        />
    </div>
</template>

<script setup>
import apiClient from "../http";
import {ref, watch} from 'vue';
import CustomDatatable from "./CustomDatatable.vue";
import {cloneDeep, debounce, isEqual} from "lodash";
import {
    MDBBtn,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import {authStore} from "@/store/auth-store";

const props = defineProps({
    ajaxPayload: {
        type: Object,
        required: false,
    },
    ajaxPayloadSync: {
        type: Object,
        required: false,
    },
    ajaxUrl: {
        type: String,
        required: true,
    },
    autoHeight: {
        type: Boolean,
        default: true,
    },
    entries: {
        type: Number,
        default: 10000,
    },
    fixedHeader: {
        type: Boolean,
        default: true,
    },
    pagination: {
        type: Boolean,
        default: false,
    },
    striped: {
        type: Boolean,
        default: true,
    },
    selectable: {
        type: Boolean,
        default: true,
    },
    multi: {
        type: Boolean,
        default: true,
    },
    exportable: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits([
    "update:loading",
    "dataset",
]);

const dataset = ref({
        columns: [],
        subColumns: [],
        rows: [],
        totals: [],
        counts: [],
    }
);
const isExporting = ref(false);
const isLoading = ref(false);

watch(() => cloneDeep(props.ajaxPayload), (selection, prevSelection) => {
    if (!isEqual(selection, prevSelection)) {
        debouncedGetValues()
    }
});

watch(isLoading, (newValue) => {
    emit("update:loading", newValue);
});

const getValues = async () => {
    isLoading.value = true;

    // Reset to default values
    dataset.value = {
        columns: [],
        subColumns: [],
        rows: [],
        totals: [],
        counts: [],
    };

    await apiClient.get(props.ajaxUrl, {
        params: {
            ...props.ajaxPayloadSync,
            ...props.ajaxPayload,
        }
    })
        .then(({data}) => {
            if (data.columns) {
                dataset.value.columns = data.columns;
            }
            if (data.subColumns) {
                dataset.value.subColumns = data.subColumns;
            }
            if (data.rows) {
                dataset.value.rows = data.rows;
            }
            if (data.totals) {
                dataset.value.totals = data.totals;
            }
            if (data.counts) {
                dataset.value.counts = data.counts;
            }
            emit("dataset", dataset.value);
        }).catch(error => {
        }).finally(() => {
            isLoading.value = false;
        });
};

const debouncedGetValues = debounce(getValues, 800);

await getValues();

const exportData = () => {
    isExporting.value = true;

    apiClient.get(props.ajaxUrl, {
        params: {
            ...props.ajaxPayloadSync,
            ...props.ajaxPayload,
            export: true,
        },
        responseType: 'blob'
    }).then((response) => {
        // Adapted from https://www.larry.dev/download-a-file-with-vue-and-axios/
        let filename = "report.xlsx";
        let disposition = response.headers['content-disposition'];
        if (disposition && disposition.indexOf('attachment') !== -1) {
            let filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            let matches = filenameRegex.exec(disposition);
            if (matches !== null && matches[1]) {
                filename = matches[1].replace(/['"]/g, '');
            }
        }

        // Let's create a link in the document that we'll programmatically 'click'.
        const link = document.createElement('a');

        // Tell the browser to associate the response data to the URL of the link we created above.
        link.href = window.URL.createObjectURL(
            new Blob([response.data])
        );

        // Tell the browser to download, not render, the file.
        link.setAttribute('download', filename);

        // Place the link in the DOM.
        document.body.appendChild(link);

        // Make the magic happen!
        link.click();
    }).catch(error => {
    }).finally(() => {
        isExporting.value = false;
    });
}

defineExpose({
    getValues,
});
</script>

<style scoped>

</style>
