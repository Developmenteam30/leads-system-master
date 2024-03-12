<template>
    <AsyncPage>
        <MDBContainer fluid class="mt-3 p-0 m-0">
            <FilterRow>
                <quick-jump interval="week" @update="setQuickJump" :disabled="isLoading"></quick-jump>
                <FilterColumn grow="1" max-width="400px">
                    <SmartSelectAjax label="Call Center" ajax-url="options/companies" v-model:selected="form.company_ids" :disabled="isLoading" multiple/>
                </FilterColumn>
                <FilterColumn grow="1" max-width="300px" :last="true">
                    <MDBRadio label="Billable" value="billable" v-model="form.view" inline name="viewOptions" :disabled="isLoading"/>
                    <MDBRadio label="Payable" value="payable" v-model="form.view" inline name="viewOptions" :disabled="isLoading"/>
                </FilterColumn>
            </FilterRow>
        </MDBContainer>
        <div class="d-flex justify-content-center">
            <MDBSpinner v-if="isLoading"/>
        </div>
        <section class="text-center">
            <MDBRow class="mt-4">
                <MDBCol xl="6" class="mb-4">
                    <MDBCard>
                        <MDBCardHeader bg="light" border="0" class="border-0 py-3">
                            <p class="mb-0"><strong>Billable Time</strong></p>
                        </MDBCardHeader>
                        <MDBCardBody class="card-body">
                            <div class="d-flex justify-content-around">
                                <div>
                                    <p class="mb-2">Hours</p>
                                    <h5>{{ reportData.billable_time }}</h5>
                                    <p class="text-success small" v-if="false">
                                        <MDBIcon icon="caret-up" class="me-1"></MDBIcon>
                                        <span>72.0%</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-2">Rate</p>
                                    <h5>{{ reportData.billable_rate }}</h5>
                                    <p class="text-success small" v-if="false">
                                        <MDBIcon icon="caret-up" class="me-1"></MDBIcon>
                                        <span>82.0%</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-2">Total</p>
                                    <h5>{{ reportData.billable_total }}</h5>
                                    <p class="text-danger small" v-if="false">
                                        <MDBIcon icon="caret-down" class="me-1"></MDBIcon>
                                        <span>12.0%</span>
                                    </p>
                                </div>
                            </div>

                            <MDBChart :key="chartKey" type="line" :data="billableData"/>
                        </MDBCardBody>
                    </MDBCard>
                </MDBCol>
                <MDBCol xl="6" class="mb-4 d-none" >
                    <MDBCard>
                        <MDBCardHeader bg="light" border="0" class="border-0 py-3">
                            <p class="mb-0"><strong>Billable Transfers</strong></p>
                        </MDBCardHeader>
                        <MDBCardBody class="card-body">
                            <div class="d-flex justify-content-around">
                                <div>
                                    <p class="mb-2">Transfers</p>
                                    <h5>{{ reportData.billable_transfers }}</h5>
                                    <p class="text-success small" v-if="false">
                                        <MDBIcon icon="caret-up" class="me-1"></MDBIcon>
                                        <span>72.0%</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-2">Rate</p>
                                    <h5>{{ reportData.billable_transfers_rate }}</h5>
                                    <p class="text-success small" v-if="false">
                                        <MDBIcon icon="caret-up" class="me-1"></MDBIcon>
                                        <span>82.0%</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-2">Total</p>
                                    <h5>{{ reportData.billable_transfers_total }}</h5>
                                    <p class="text-danger small" v-if="false">
                                        <MDBIcon icon="caret-down" class="me-1"></MDBIcon>
                                        <span>12.0%</span>
                                    </p>
                                </div>
                            </div>

                            <MDBChart :key="chartKey" type="line" :data="transfersData" v-if="transfersData"/>
                        </MDBCardBody>
                    </MDBCard>
                </MDBCol>
            </MDBRow>
        </section>
    </AsyncPage>
</template>

<script setup>
import {
    MDBCard,
    MDBCardBody,
    MDBCardHeader,
    MDBChart,
    MDBCol,
    MDBContainer,
    MDBIcon,
    MDBRadio,
    MDBRow,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import AsyncPage from '@/components/AsyncPage.vue';
import {ref, watch} from "vue";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";
import QuickJump from "@/components/QuickJump.vue";
import FilterRow from "@/components/FilterRow.vue";
import FilterColumn from "@/components/FilterColumn.vue";
import {DateTime} from "luxon";
import {formatCurrency, formatNumber} from "@/bootstrap";
import apiClient from "@/http";
import {debounce} from "lodash";

const chartKey = ref(0);
const isLoading = ref(false);
const form = ref({
    company_ids: '',
    view: 'billable',
    start_date: DateTime.now().startOf("month").toISODate(),
    end_date: DateTime.now().endOf("month").toISODate(),
});

const reportData = ref({
    billable_time: formatNumber(0),
    billable_rate: formatCurrency(0),
    billable_total: formatCurrency(0),
    billable_transfers: formatNumber(0),
    billable_transfers_rate: formatCurrency(0),
    billable_transfers_total: formatCurrency(0),
});
const billableData = ref({});
const transfersData = ref({});

const setQuickJump = (values) => {
    form.value.start_date = values.startDate;
    form.value.end_date = values.endDate;
}

watch(form.value, (selection, prevSelection) => {
    debouncedGetReport()
});

const getReport = () => {
    isLoading.value = true;

    apiClient.get('reports/dashboard', {params: form.value})
        .then(({data}) => {
            reportData.value.billable_time = formatNumber(data.totals.billable_time || 0);
            reportData.value.billable_rate = formatCurrency(data.totals.billable_rate || 0);
            reportData.value.billable_total = formatCurrency(data.totals.billable_total || 0);
            reportData.value.billable_transfers = formatNumber(data.totals.billable_transfers || 0);
            reportData.value.billable_transfers_rate = formatCurrency(data.totals.billable_transfers_rate || 0);
            reportData.value.billable_transfers_total = formatCurrency(data.totals.billable_transfers_total || 0);
            billableData.value = data.billable_time;
            transfersData.value = data.billable_transfers;
            chartKey.value++;
        }).catch(error => {
    }).finally(() => {
        isLoading.value = false;
    });
};

const debouncedGetReport = debounce(getReport, 500);
</script>
