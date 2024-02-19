<template>
    <h2>Internal Campaigns</h2>
    <AsyncPage>
        <MDBBtn color="primary" @click="setModalValues(null)">Add a Campaign</MDBBtn>
        <CustomDatatableAjax
            :key="key"
            :ajax-url="`campaigns/manage`"
            class="mt-3"
            clickableRows
            @row-click-values="setModalValues"
            @update:loading="(value) => isLoading = value"
            :exportable="true"
            :show-count="true"
        />
    </AsyncPage>
    <CampaignModal
        :showModal="showModal"
        @update:showModal="updateModal"
        @reload="reloadResults"
        :modalValues="modalValues"
    >
    </CampaignModal>
</template>

<script setup>
import {
    MDBBtn,
} from "mdb-vue-ui-kit";
import {ref} from 'vue';
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import {value} from "lodash/seq";
import AsyncPage from "@/components/AsyncPage.vue";
import CampaignModal from "@/views/admin/campaigns/CampaignModal.vue";

const showModal = ref(false);
const isLoading = ref(false);
const modalValues = ref({});

const key = ref(0);

const setModalValues = (values) => {
    if (values !== null) {
        modalValues.value = values;
    } else {
        modalValues.value = {
            id: null,
        };
    }
    showModal.value = true;
}

function updateModal(value) {
    showModal.value = value;
}

function reloadResults() {
    key.value++;
}
</script>
<style lang="scss">
</style>
