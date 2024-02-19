<template>
    <h2>{{ type }} Profile</h2>
    <MDBRow class="mt-3">
        <MDBCol col="12" md="6" lg="3">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Agent</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayDashIfBlank(agent?.agent_name) }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="3" class="mt-3 mt-lg-0">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Hire Date</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayDashIfBlank(formattedHireDate) }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="3">
            <MDBCard text="center" class="bg-opacity-25 bg-info mt-3 mt-md-0">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Hourly Rate</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText>
                        <strong v-if="showHourlyRate" class="me-3">${{ displayDashIfBlank(formatNumberDecimals(agent?.latest_active_effective_date?.payable_rate_belize, 2)) }} BZD</strong>
                        <MDBBtn class="show-hourly-rate" size="sm" color="primary" @click="showHourlyRate = !showHourlyRate">
                            <template v-if="showHourlyRate">Hide</template>
                            <template v-else>Show</template>
                        </MDBBtn>
                    </MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="3" class="mt-3 mt-lg-0">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Manager</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayDashIfBlank(agent?.team?.manager?.agent_name) }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
    </MDBRow>

    <MDBRow class="mt-3">
        <MDBCol col="12" md="6" lg="4">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Email</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayDashIfBlank(agent?.email) }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="4" class="mt-3 mt-lg-0">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Campaign</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayDashIfBlank(agent?.latest_active_effective_date?.product?.name) }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="4">
            <MDBCard text="center" class="bg-opacity-25 bg-info mt-3 mt-md-0">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Team</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayDashIfBlank(agent?.team?.name) }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
    </MDBRow>

    <MDBRow class="mt-3">
        <MDBCol col="12" md="6" lg="4">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Vacation Accrued</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ agent && agent.vacationAccrued ? agent.vacationAccrued + ' Hours' : 'Time will accrue 90 days after employment' }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="4" class="mt-3 mt-lg-0">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Vacation Taken</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayZeroIfBlank(agent?.vacationTaken) + ' Hours' }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="4">
            <MDBCard text="center" class="bg-opacity-25 bg-info mt-3 mt-md-0">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Vacation Remaining</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayZeroIfBlank(agent?.vacationRemaining) + ' Hours' }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
    </MDBRow>
    <MDBRow class="mt-3">
        <MDBCol col="12" md="6" lg="4">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Sick Accrued</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayZeroIfBlank(agent?.sickAccrued) + ' Days' }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="4" class="mt-3 mt-lg-0">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Sick Taken</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayZeroIfBlank(agent?.sickTaken)  + ' Days'}}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="4">
            <MDBCard text="center" class="bg-opacity-25 bg-info mt-3 mt-md-0">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">Sick Remaining</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayZeroIfBlank(agent?.sickRemaining) + ' Days' }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
    </MDBRow>
    <MDBRow class="mt-3">
        <MDBCol col="12" md="6" lg="4">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">PTO Accrued</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayZeroIfBlank(agent?.ptoAccrued) + ' Hours' }}</strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="4" class="mt-3 mt-lg-0">
            <MDBCard text="center" class="bg-opacity-25 bg-info">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">PTO Taken</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText>
                        <strong>{{ displayZeroIfBlank(agent?.ptoTaken) + ' Hours' }}</strong>
                        <a class="m-1" href="#" role="button" style="color:#e4a11b" @click="emit('pending:click')">
                            <MDBIcon icon="info-circle" size="lg"></MDBIcon>
                        </a>
                    </MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
        <MDBCol col="12" md="6" lg="4">
            <MDBCard text="center" class="bg-opacity-25 bg-info mt-3 mt-md-0">
                <MDBCardHeader class="performance-header-background"><h5 class="mb-0">PTO Remaining</h5></MDBCardHeader>
                <MDBCardBody class="p-2">
                    <MDBCardText><strong>{{ displayZeroIfBlank(agent?.ptoRemaining) + ' Hours' }} </strong></MDBCardText>
                </MDBCardBody>
            </MDBCard>
        </MDBCol>
    </MDBRow>
</template>

<script setup>
import {
    MDBBtn,
    MDBCard,
    MDBCardBody,
    MDBCardHeader,
    MDBCardText,
    MDBCol,
    MDBIcon,
    MDBRow,
} from "mdb-vue-ui-kit";
import {displayDashIfBlank, displayZeroIfBlank, formatNumber, formatNumberDecimals} from "@/helpers";
import {computed, ref} from "vue";
import {DateTime} from "luxon";

const props = defineProps({
    type: {
        type: String,
        required: true,
        default: 'Agent',
    },
    agent: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['pending:click']);

const showHourlyRate = ref(false);

const formattedHireDate = computed(() =>
    props.agent && props.agent.effectiveHireDate ? DateTime.fromISO(props.agent.effectiveHireDate, {setZone: true}).toLocaleString(DateTime.DATE_MED) : ''
);

</script>
<style lang="scss">
.show-hourly-rate {
    font-size: 0.75em;
}
</style>
