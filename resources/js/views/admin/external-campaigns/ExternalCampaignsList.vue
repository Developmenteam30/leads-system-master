<template>
    <AbstractList
        ajax-url="external-campaigns/manage"
        title-singular="External Campaign"
        title-plural="External Campaigns"
        :form="form"
        :filter-form="filterForm"
        @update:form="(value) => form = value"
        :defaults="defaults"
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
            <MDBInput label="Convoso Campaign ID" v-model="form.id" :maxlength=255 :required="true"/>

            <MDBInput label="Name" v-model="form.name" :maxlength=255 :required="true"/>

            <SmartSelectAjax label="Internal Campaign" ajax-url="options/dialer_products" v-model:selected="form.campaign_id"/>

            <MDBCheckbox v-if="form.id" label="Archived" v-model="form.isArchived"/>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBAlert, MDBCheckbox, MDBContainer, MDBInput} from "mdb-vue-ui-kit";
import {ref} from "vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import FilterRow from "@/components/FilterRow.vue";
import FilterColumn from "@/components/FilterColumn.vue";

const isLoading = ref(false);
const form = ref({
    id: null,
    name: '',
    campaign_id: '',
});

const filterForm = ref({
    search: '',
    include_archived: false,
});
</script>
