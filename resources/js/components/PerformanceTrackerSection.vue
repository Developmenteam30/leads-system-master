<template>
    <MDBRow class="mt-lg-4">
        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="Calls Handled"
                :average="averages.calls"
                :value="values.calls"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>

        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="Successful Transfers"
                :average="averages.transfers"
                :value="values.transfers"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>

        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="Conversion Rate"
                :average="averages.conversion_rate"
                :value="values.conversion_rate"
                append="%"
                :formatter="value => formatNumberDecimals(value, 1)"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>
    </MDBRow>


    <MDBRow class="mt-lg-4">
        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="Avg Transfer Duration"
                :average="averages.successful_transfers_bill_time"
                :value="values.successful_transfers_bill_time"
                :cardtypeatd=true
                :atdcolor="getColorClass(averages.successful_transfers_bill_time, values.successful_transfers_bill_time)"
                :atdicon="getIconClass(averages.successful_transfers_bill_time, values.successful_transfers_bill_time)"
                :formatter="formatSecondsToTime"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>

        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="STs under 5 Mins"
                :average="averages.under_5_min"
                :value="values.under_5_min"
                :over="false"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>

        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="% STs under 5 Mins"
                :average="averages.under_5min_pct"
                :value="values.under_5min_pct"
                append="%"
                :over="false"
                :formatter="value => formatNumberDecimals(value, 1)"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>
    </MDBRow>

    <MDBRow class="mt-lg-4">
        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="Billable Hours"
                :average="averages.billable_time"
                :value="values.billable_time"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>

        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="Billable Transfers"
                :average="averages.billable_transfers"
                :value="values.billable_transfers"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>

        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="BTs per Billable Hour"
                :average="averages.bt_per_bh"
                :value="values.bt_per_bh"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>
    </MDBRow>

    <MDBRow class="mt-lg-4">
        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="Cost per Billable Transfer"
                :average="averages.cost_per_bt"
                :value="values.cost_per_bt"
                prepend="$"
                :over="false"
                :formatter="formatNumberDecimals"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>

        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="Estimated Sales"
                :average="averages.over_60_min"
                :value="values.over_60_min"
                :formatter="formatNumberDecimals"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>

        <MDBCol lg="4" class="mb-4 mb-lg-0">
            <PerformanceTrackerCard
                title="Estimated Cost per Sale"
                :average="averages.cost_per_sale"
                :value="values.cost_per_sale"
                prepend="$"
                :over="false"
                :formatter="formatNumberDecimals"
                :show-goal="false"
                :performant="performant"
            ></PerformanceTrackerCard>
        </MDBCol>
    </MDBRow>
</template>

<script setup>
import {
    MDBCol,
    MDBRow,
} from "mdb-vue-ui-kit";
import {formatNumberDecimals, formatSecondsToTime} from "@/helpers";
import PerformanceTrackerCard from "@/components/PerformanceTrackerCard.vue";

const props = defineProps({
    values: {
        type: Object,
        required: true,
    },
    averages: {
        type: Object,
        required: true,
    },
    performant: {
        type: String,
    }
});
function getColorClass(value, average) {
    const lowerThreshold = value * 0.8; // 20% less
    const upperThreshold = value * 1.2; // 20% higher
    
    // Determine the color class based on the condition
    if (average >= lowerThreshold && average <= upperThreshold) {
        return 'text-primary'; // Within the threshold range
    } else {
        return 'text-danger'; // Outside the threshold range
    }
}
function getIconClass(value, average) {
    const lowerThreshold = value * 0.8; // 20% less
    const upperThreshold = value * 1.2; // 20% higher
    
    // Determine the color class based on the condition
    if (average < lowerThreshold) {
        return 'down'; // Within the threshold range
    } else if(average > upperThreshold){
        return 'up'; // Outside the threshold range
    } else {
        return 'none'; // Outside the threshold range
    }
}
</script>
