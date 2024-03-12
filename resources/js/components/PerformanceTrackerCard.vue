<template>
    <MDBCard class="bg-opacity-25" text="center" :bg="goalIndicator">
        <MDBCardHeader class="performance-header-background"><h5 class="mb-0">{{ title }}</h5></MDBCardHeader>
        <MDBCardBody class="p-2">
            <div class="d-flex justify-content-between">
                <div class="text-center w-50">
                    <h6 class="value-title">{{ performant }} <MDBIcon v-if="atdicon == 'up'" icon="caret-up" style="color: red" size="xl"></MDBIcon><MDBIcon v-if="atdicon == 'down'" icon="caret-down" style="color: red" size="xl"></MDBIcon></h6>
                    <h5 class="mb-0 performance-value" :class="{ 'text-primary': atdcolor == 'text-primary', 'text-danger': atdcolor == 'text-danger' }">{{ prepend }}{{ formatter(value) }}{{ append }} </h5>
                </div>
                <div class="text-center w-50">
                    <h6 class="value-title">Average</h6>
                    <h5 class="mb-0 text-primary performance-value">{{ prepend }}{{ formatter(average) }}{{ append }}</h5>
                </div>
            </div>
        </MDBCardBody>
        <MDBCardFooter><strong v-if="showGoal">{{ goalText }}</strong><span v-else>&nbsp;</span></MDBCardFooter>
    </MDBCard>
</template>

<script setup>
import {
    MDBBadge,
    MDBCard,
    MDBCardBody,
    MDBCardFooter,
    MDBCardHeader,
    MDBCardTitle,
    MDBIcon,
} from "mdb-vue-ui-kit";
import {formatNumber} from "@/helpers";
import {computed} from "vue";
import {round} from "lodash";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    performant: {
        type: String,
        default: "Agent"
    },
    value: {
        type: [String, Number, null],
        required: true,
    },
    average: {
        type: [String, Number, null],
        required: true,
    },
    over: {
        type: Boolean,
        default: true,
    },
    cardtypeatd: {
        type: Boolean,
        default: false,
    },
    atdicon: {
        type: String,
        default: 'none',
    },
    atdcolor: {
        type: String,
        default: 'text-primary',
    },

    append: {
        type: String,
        default: '',
    },
    prepend: {
        type: String,
        default: '',
    },
    formatter: {
        type: Function,
        default: formatNumber,
    },
    showGoal: {
        type: Boolean,
        default: true,
    }
});

const goalCalculator = () => {
    if (props.over) {
        if (props.value >= props.average * 1.2) {
            return 1;
        } else if (props.value <= props.average * 0.8) {
            return -1;
        } else {
            return 0;
        }
    } else {
        if (props.value <= props.average * 1.2) {
            return 1;
        } else if (props.value >= props.average * 0.8) {
            return -1;
        } else {
            return 0;
        }
    }
};

const goalIndicator = computed(() => {
    let value = goalCalculator();
    return value === 1 ? 'success' : (value === -1 ? 'danger' : 'warning');
});

const goalText = computed(() => {
    let goalValue = goalCalculator();
    let value = props.value === 0 ? 1 : props.value;
    let modifier = props.over ? 1.2 : 0.8;
    return goalValue === 1 ? 'Great Work' : (goalValue === -1 ? 'Goal: ' + (props.prepend + props.formatter(round(value * modifier, 2)) + props.append) : 'Keep Pushing');
});
</script>
<style lang="scss" scoped>
.value-title {
    font-size: 0.75em;
    margin: 0;
}
</style>
