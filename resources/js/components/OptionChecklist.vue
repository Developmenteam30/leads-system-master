<template>
    <h5 v-if="header" class="mt-3">{{ header }}</h5>
    <MDBBtn size="sm" color="primary" v-if="showCheckAll" @click="checkAll">Check All</MDBBtn>
    <MDBBtn size="sm" color="primary" v-if="showClearAll" @click="uncheckAll">Uncheck All</MDBBtn>

    <div class="mt-3"></div>
    <div v-for="option in options" class="mb-2">
        <input type="checkbox" :id="`${uniqueId}_${option.value}`" :value="option.value" v-model="selectedLocal">
        <label :for="`${uniqueId}_${option.value}`" class="ms-2">{{ option.text }}</label>
        <div v-if="descriptionField && option[descriptionField]" class="ms-4 mb-3"><small>{{ option[descriptionField] }}</small></div>
    </div>
</template>

<script setup>
import {onMounted, ref} from "vue";
import {uuid} from "vue-uuid";
import apiClient from "@/http";
import {useModelWrapper} from "@/modelWrapper";
import {MDBBtn} from "mdb-vue-ui-kit";

const props = defineProps({
    ajaxUrl: {
        type: String,
        required: true,
    },
    selected: [String, Array, Number],
    header: [String],
    showClearAll: {
        type: Boolean,
        default: false,
    },
    showCheckAll: {
        type: Boolean,
        default: false,
    },
    descriptionField: {
        type: String,
    }
});

const emit = defineEmits([
    "update:selected",
]);

const options = ref([]);
const uniqueId = uuid.v1();

const getOptions = () => {
    apiClient.get(props.ajaxUrl)
        .then(({data}) => {
            options.value = data;
        }).catch(error => {
    }).finally(() => {
    });
};

onMounted(() => {
    getOptions()
});

const checkAll = () => {
    selectedLocal.value.splice(0);
    options.value.forEach(option => {
        selectedLocal.value.push(option.value);
    });
}

const uncheckAll = () => {
    selectedLocal.value.splice(0);
}

const selectedLocal = useModelWrapper(props, emit, 'selected');
</script>
