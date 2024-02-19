<template>
    <AbstractList
        ajax-url="teams/manage"
        title-singular="Team"
        title-plural="Teams"
        :form="form"
        :filter-form="filterForm"
        @update:form="(value) => form = value"
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
            <MDBInput label="Team Name" v-model="form.name"/>

            <SmartSelectAjax label="Team Manager" ajax-url="options/dialer_employees" v-model:selected="form.manager_agent_id"/>

            <SmartSelectAjax label="Team Leads" ajax-url="options/dialer_employees" v-model:selected="form.team_lead_agent_ids" multiple/>

            <MDBCheckbox label="Archived" v-model="form.isArchived"/>

            <template v-if="form.id">
                <h3 class="mt-3">Team Members</h3>
                <MDBBtn size="sm" color="primary" @click="showTeamMembers = !showTeamMembers">
                    <template v-if="showTeamMembers">Hide</template>
                    <template v-else>Show</template>
                </MDBBtn>
                <MDBCollapse
                    id="teamMembersCollapse"
                    v-model="showTeamMembers"
                >
                    <CustomDatatableAjax
                        :ajax-url="`teams/members/${form.id}`"
                        :entries="10000"
                        striped
                        fixedHeader
                        class="mt-0"
                        :pagination="false"
                        :auto-height="false"
                        :selectable="false"
                        @loading="(value) => isLoading = value"
                    />
                </MDBCollapse>
            </template>
        </template>
    </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBBtn, MDBCheckbox, MDBCollapse, MDBContainer, MDBInput} from "mdb-vue-ui-kit";
import {ref} from "vue";
import CustomDatatableAjax from "@/components/CustomDatatableAjax.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import FilterRow from "@/components/FilterRow.vue";

const form = ref({
    id: null,
});

const filterForm = ref({
    search: '',
    include_archived: false,
});

const showTeamMembers = ref(false);
const isLoading = ref(false);
</script>
<style lang="scss">
</style>
