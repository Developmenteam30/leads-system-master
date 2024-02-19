<template>
    <component :is="embedded ? 'h5' : 'h2'">{{ titlePlural }}</component>
    <AsyncPage>
        <MDBBtn v-if="showAddButton" color="primary" :size="embedded ? 'sm' : 'md'" @click="setEditModalValues(null)">Add {{ titleSingular }}</MDBBtn>
        <slot name="filters"></slot>
        <CustomDatatableAjax
            :key="key"
            :ajax-url="ajaxUrl"
            :ajax-payload="{ ...filterForm }"
            :ajax-payload-sync="filterFormSync"
            :class="embedded ? 'mt-1' : 'mt-3'"
            :clickable-rows="!hasActions"
            @row-click-values="setClickValues"
            @update:loading="(value) => isLoading = value"
            :exportable="exportable"
            @render="setActions"
            :show-count="showCount"
            ref="datatable"
            :auto-height="false"
            @selected-rows="(value) => emit('selected-rows', value)"
            @dataset="(value) => dataset = value"
        />
    </AsyncPage>
    <AbstractEditModal
        :title-singular="titleSingular"
        :showModal="showEditModal"
        @update:showModal="(value) => showEditModal = value"
        @reload="reloadResults"
        :ajax-url="ajaxEditUrlBase"
        :form="form"
        :is-fetching="isFetching"
        :show-edit-modal-footer="showEditModalFooter"
    >
        <slot name="edit-modal"></slot>
    </AbstractEditModal>
    <AbstractViewModal
        :title-singular="titleSingular"
        :showModal="showViewModal"
        @update:showModal="(value) => showViewModal = value"
        :ajax-url="ajaxEditUrlBase"
        :form="form"
        :is-fetching="isFetching"
    >
        <slot name="view-modal"></slot>
    </AbstractViewModal>
    <AbstractDeleteModal
        :title-singular="titleSingular"
        :showModal="showDeleteModal"
        @update:showModal="(value) => showDeleteModal = value"
        @reload="reloadResults"
        :ajax-url="ajaxEditUrlBase"
        :form="form"
        :is-fetching="isFetching"
    >
        <slot name="delete-modal">
            <slot name="view-modal"></slot>
        </slot>
    </AbstractDeleteModal>
    <AbstractRestoreModal
        :title-singular="titleSingular"
        :showModal="showRestoreModal"
        @update:showModal="(value) => showRestoreModal = value"
        @reload="reloadResults"
        :ajax-url="ajaxEditUrlBase"
        :form="form"
        :is-fetching="isFetching"
    >
        <slot name="restore-modal">
            <slot name="view-modal"></slot>
        </slot>
    </AbstractRestoreModal>
    <AbstractHistoryModal
        :title-singular="titleSingular"
        :showModal="showHistoryModal"
        @update:showModal="(value) => showHistoryModal = value"
        :ajax-url="ajaxEditUrlBase"
        :form="form"
        :is-fetching="isFetching"
    >
        <slot name="history-modal"></slot>
    </AbstractHistoryModal>
    <AbstractUploadModal
        :title-singular="titleSingular"
        :showModal="showUploadModal"
        @update:showModal="(value) => showUploadModal = value"
        @reload="reloadResults"
        :ajax-url="ajaxEditUrlBase"
        :form="form"
        :is-fetching="isFetching"
    >
        <slot name="upload-modal">
        </slot>
    </AbstractUploadModal>
</template>

<script setup>
import {
    MDBBtn,
} from "mdb-vue-ui-kit";
import {ref, watch} from 'vue';
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import {value} from "lodash/seq";
import AsyncPage from "@/components/AsyncPage.vue";
import AbstractEditModal from "@/components/AbstractEditModal.vue";
import {useModelWrapper} from "@/modelWrapper";
import {find, cloneDeep} from "lodash";
import AbstractViewModal from "@/components/AbstractViewModal.vue";
import apiClient from "@/http";
import AbstractDeleteModal from "@/components/AbstractDeleteModal.vue";
import AbstractHistoryModal from "@/components/AbstractHistoryModal.vue";
import AbstractRestoreModal from "@/components/AbstractRestoreModal.vue";
import AbstractUploadModal from "@/components/AbstractUploadModal.vue";

const props = defineProps({
    titleSingular: {
        type: String,
        required: true,
    },
    titlePlural: {
        type: String,
        required: true,
    },
    exportable: {
        type: Boolean,
        default: true,
    },
    ajaxUrl: {
        type: String,
        required: true,
    },
    ajaxEditUrlBase: {
        type: String,
    },
    form: {
        type: Object,
    },
    filterForm: {
        type: Object,
    },
    filterFormSync: {
        type: Object,
    },
    hasActions: {
        type: Boolean,
        default: false,
    },
    showCount: {
        type: Boolean,
        default: true,
    },
    defaults: {
        type: Object,
        default: {
            id: '',
        },
    },
    embedded: {
        type: Boolean,
        default: false,
    },
    showAddButton: {
        type: Boolean,
        default: true,
    },
    showEditModalFooter: {
        type: Boolean,
        default: true,
    },
    emitViewAction: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits([
    "reload",
    "selected-rows",
    "update:form",
    "update:loading",
    "action:view",
]);

const showEditModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const showRestoreModal = ref(false);
const showHistoryModal = ref(false);
const showUploadModal = ref(false);
const isLoading = ref(false);
const isFetching = ref(false);
const datatable = ref(null);
const ajaxEditUrlBase = props.ajaxEditUrlBase || props.ajaxUrl;
const dataset = ref({
        columns: [],
        subColumns: [],
        rows: [],
        totals: [],
        counts: [],
    }
);
const key = ref(0);

const setClickValues = (values) => {
    if (!props.hasActions) {
        setEditModalValues(values);
    }
};

const setEditModalValues = (values) => {
    getRemoteValues(values);
    showEditModal.value = true;
}

const setViewModalValues = (values) => {
    getRemoteValues(values);
    showViewModal.value = true;
}

const setHistoryModalValues = (values) => {
    formLocal.value = values;
    showHistoryModal.value = true;
}

const setDeleteModalValues = (values) => {
    getRemoteValues(values);
    showDeleteModal.value = true;
}

const setUploadModalValues = (values) => {
    formLocal.value = {
        id: values.id,
    };
    showUploadModal.value = true;
}

const setRestoreModalValues = (values) => {
    getRemoteValues(values);
    showRestoreModal.value = true;
}

const getRemoteValues = (values) => {
    if (values !== null && values.id) {
        isFetching.value = true;
        apiClient.get(`${ajaxEditUrlBase}/${values.id}`)
            .then(({data}) => {
                formLocal.value = data;
            }).catch(error => {
        }).finally(() => {
            isFetching.value = false;
        });
    } else {
        formLocal.value = cloneDeep(props.defaults);
    }
}

const reloadResults = () => {
    key.value++;
    emit("reload");
}

const formLocal = useModelWrapper(props, emit, 'form');

const setActions = () => {
    if (props.hasActions) {
        Array.from(document.getElementsByClassName("view-btn")).forEach(btn => {
            if (btn.getAttribute("click-listener") !== "true") {
                btn.addEventListener("click", () => {
                    if (props.emitViewAction) {
                        const row = find(dataset.value.rows, {id: parseInt(btn.attributes["data-mdb-number"].value)});
                        if (row) {
                            emit("action:view", row);
                        }
                    } else {
                        setViewModalValues({id: btn.attributes["data-mdb-number"].value});
                    }
                });
                btn.setAttribute("click-listener", "true");
            }
        });
        Array.from(document.getElementsByClassName("edit-btn")).forEach(btn => {
            if (btn.getAttribute("click-listener") !== "true") {
                btn.addEventListener("click", (e) => {
                    setEditModalValues({id: btn.attributes["data-mdb-number"].value});
                });
                btn.setAttribute("click-listener", "true");
            }
        });
        Array.from(document.getElementsByClassName("history-btn")).forEach(btn => {
            if (btn.getAttribute("click-listener") !== "true") {
                btn.addEventListener("click", (e) => {
                    setHistoryModalValues({id: btn.attributes["data-mdb-number"].value});
                });
                btn.setAttribute("click-listener", "true");
            }
        });
        Array.from(document.getElementsByClassName("delete-btn")).forEach(btn => {
            if (btn.getAttribute("click-listener") !== "true") {
                btn.addEventListener("click", (e) => {
                    setDeleteModalValues({id: btn.attributes["data-mdb-number"].value});
                });
                btn.setAttribute("click-listener", "true");
            }
        });
        Array.from(document.getElementsByClassName("restore-btn")).forEach(btn => {
            if (btn.getAttribute("click-listener") !== "true") {
                btn.addEventListener("click", (e) => {
                    setRestoreModalValues({id: btn.attributes["data-mdb-number"].value});
                });
                btn.setAttribute("click-listener", "true");
            }
        });
        Array.from(document.getElementsByClassName("upload-btn")).forEach(btn => {
            if (btn.getAttribute("click-listener") !== "true") {
                btn.addEventListener("click", (e) => {
                    setUploadModalValues({id: btn.attributes["data-mdb-number"].value});
                });
                btn.setAttribute("click-listener", "true");
            }
        });
    }
};

watch(isLoading, (newValue) => {
    emit("update:loading", newValue);
});

const getValues = () => {
    datatable.value.$.exposed.getValues()
}

defineExpose({
    getValues,
});
</script>
<style lang="scss">
</style>
