<template>
    <AbstractList
        ajax-url="petty-cash/manage"
        title-singular="Petty Cash Entry"
        title-plural="Petty Cash Entries"
        :form="form"
        :filter-form="filterForm"
        @update:form="(value) => form = value"
        @update:loading="(value) => isLoading = value"
        :defaults="defaults"
    >
        <template v-slot:filters>
            <MDBContainer fluid class="mt-3 p-0 m-0">
                <FilterRow>
                    <quick-jump interval="default" @update="setQuickJump" :disabled="isLoading" :start-date="filterForm.start_date" :end-date="filterForm.end_date"></quick-jump>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="Location" ajax-url="options/petty-cash-locations" v-model:selected="filterForm.petty_cash_location_id" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="Vendor" ajax-url="options/petty-cash-vendors" v-model:selected="filterForm.petty_cash_vendor_id" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" max-width="300px">
                        <SmartSelectAjax label="Reason" ajax-url="options/petty-cash-reasons" v-model:selected="filterForm.petty_cash_reason_id" :disabled="isLoading"/>
                    </FilterColumn>
                    <FilterColumn grow="1" :last="true">
                        <MDBCheckbox label="Include archived" v-model="filterForm.include_archived" :disabled="isLoading"/>
                    </FilterColumn>
                </FilterRow>
            </MDBContainer>
        </template>
        <template v-slot:edit-modal>
            <MDBDatepicker v-model="form.date" inline inputToggle label="Date" format="YYYY-MM-DD" confirmDateOnSelect :required="true"/>

            <MDBBtn class="mb-3" color="primary" size="sm" @click="showLocationModal(null)">Add Location</MDBBtn>
            <SmartSelectAjax :key="locationKey" label="Location" ajax-url="options/petty-cash-locations" v-model:selected="form.petty_cash_location_id" :show-required="true"/>

            <MDBBtn class="mb-3" color="primary" size="sm" @click="showVendorModal(null)">Add Vendor</MDBBtn>
            <SmartSelectAjax :key="vendorKey" label="Vendor" ajax-url="options/petty-cash-vendors" v-model:selected="form.petty_cash_vendor_id" :show-required="true"/>

            <MDBBtn class="mb-3" color="primary" size="sm" @click="showReasonModal(null)">Add Reason</MDBBtn>
            <SmartSelectAjax :key="reasonKey" label="Reason" ajax-url="options/petty-cash-reasons" v-model:selected="form.petty_cash_reason_id" :show-required="true"/>

            <MDBBtn class="mb-3" color="primary" size="sm" @click="showNoteModal(null)">Add Note</MDBBtn>
            <SmartSelectAjax :key="noteKey" label="Notes" ajax-url="options/petty-cash-notes" v-model:selected="form.petty_cash_note_id"/>

            <MDBInput label="Amount" v-model="form.absAmount" :maxlength=255 :required="true"/>

            <MDBRadio
                label="Deposit"
                name="type"
                v-model="form.type"
                value="in"
                :required="true"
            />
            <MDBRadio
                label="Payment"
                name="type"
                v-model="form.type"
                value="out"
                :required="true"
            />

            <MDBCheckbox v-if="form.id" label="Archived" v-model="form.isArchived"/>
        </template>
    </AbstractList>

    <ManageLocation
        :showModal="locationModal"
        @update:showModal="updateLocationModal"
        @update:id="(value) => form.petty_cash_location_id = value"
        title="Add Location"
        :modalValues="locationModalValues"
    >
    </ManageLocation>
    <ManageVendor
        :showModal="vendorModal"
        @update:showModal="updateVendorModal"
        @update:id="(value) => form.petty_cash_vendor_id = value"
        title="Add Vendor"
        :modalValues="vendorModalValues"
    >
    </ManageVendor>
    <ManageReason
        :showModal="reasonModal"
        @update:showModal="updateReasonModal"
        @update:id="(value) => form.petty_cash_reason_id = value"
        title="Add Reason"
        :modalValues="reasonModalValues"
    >
    </ManageReason>
    <ManageNote
        :showModal="noteModal"
        @update:showModal="updateNoteModal"
        @update:id="(value) => form.petty_cash_note_id = value"
        title="Add Note"
        :modalValues="noteModalValues"
    >
    </ManageNote>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBBtn, MDBCheckbox, MDBContainer, MDBDatepicker, MDBInput, MDBRadio,} from "mdb-vue-ui-kit";
import { ref, nextTick, watch} from "vue";
import ManageLocation from "@/views/admin/petty-cash/ManageLocation.vue";
import ManageNote from "@/views/admin/petty-cash/ManageNote.vue";
import ManageReason from "@/views/admin/petty-cash/ManageReason.vue";
import ManageVendor from "@/views/admin/petty-cash/ManageVendor.vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import FilterRow from "@/components/FilterRow.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import QuickJump from "@/components/QuickJump.vue";

const locationKey = ref(0);
const locationModal = ref(false);
const locationModalValues = ref({});

const vendorKey = ref(0);
const vendorModal = ref(false);
const vendorModalValues = ref({});

const reasonKey = ref(0);
const reasonModal = ref(false);
const reasonModalValues = ref({});

const noteKey = ref(0);
const noteModal = ref(false);
const noteModalValues = ref({});

const isLoading = ref(false);

const form = ref({
    id: null,
    type: 'out',
});

const defaults = ref({
    id: null,
    type: 'out',
});

const childrenReasonsUrl = ref("");
watch(
    () => form.value.petty_cash_parent_id,
    (petty_cash_parent_id) => {
        childrenReasonsUrl.value = petty_cash_parent_id
            ? `options/petty-cash-reasons-children/${petty_cash_parent_id}`
            : "";
  }
);

const filterForm = ref({
    start_date: '',
    end_date: '',
    petty_cash_location_id: '',
    petty_cash_vendor_id: '',
    petty_cash_reason_id: '',
    include_archived: false,
});

const showLocationModal = (values) => {
    if (values !== null) {
        locationModalValues.value = {
            ...values,
            error: '',
        };
    } else {
        locationModalValues.value = {
            id: null,
            error: '',
        };
    }
    locationModal.value = true;
}

const updateLocationModal = (value) => {
    locationModal.value = value;
    if (value === false) {
        nextTick(() => {
            locationKey.value++;
        });
    }
}

const showVendorModal = (values) => {
    if (values !== null) {
        vendorModalValues.value = {
            ...values,
            error: '',
        };
    } else {
        vendorModalValues.value = {
            id: null,
            error: '',
        };
    }
    vendorModal.value = true;
}

const updateVendorModal = (value) => {
    vendorModal.value = value;
    if (value === false) {
        nextTick(() => {
            vendorKey.value++;
        });
    }
}

const showReasonModal = (values) => {
    if (values !== null) {
        reasonModalValues.value = {
            ...values,
            error: '',
        };
    } else {
        reasonModalValues.value = {
            id: null,
            error: '',
        };
    }
    reasonModal.value = true;
}

const updateReasonModal = (value) => {
    reasonModal.value = value;
    if (value === false) {
        nextTick(() => {
            reasonKey.value++;
        });
    }
}

const showNoteModal = (values) => {
    if (values !== null) {
        noteModalValues.value = {
            ...values,
            error: '',
        };
    } else {
        noteModalValues.value = {
            id: null,
            error: '',
        };
    }
    noteModal.value = true;
}

const updateNoteModal = (value) => {
    noteModal.value = value;
    if (value === false) {
        nextTick(() => {
            noteKey.value++;
        });
    }
}

const setQuickJump = (values) => {
    filterForm.value.start_date = values.startDate;
    filterForm.value.end_date = values.endDate;
}
</script>
<style lang="scss">
</style>
