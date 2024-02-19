<template>
    <MDBCol>
        <MDBCard text="center" class="bg-opacity-25"
                 :class="hoursStatus === null && dispoStatus === null ? 'not-applicable' : (hoursStatus === true && dispoStatus === true ? 'bg-success' : 'bg-danger')">
            <MDBCardBody class="p-2">
                <MDBCardTitle>{{ day }}</MDBCardTitle>
                <MDBCardText>
                    <div class="d-flex justify-content-between">
                        <div class="text-center w-50">
                            <h6 class="value-title">Hours</h6>
                            <h5>
                                <MDBSpinner size="sm" v-if="isLoading"/>
                                <span v-else-if="hoursStatus === null">-</span>
                                <i v-else-if="hoursStatus" class="fas fa-check text-success"></i>
                                <i v-else class="fas fa-times text-danger"></i>
                            </h5>
                        </div>
                        <div class="text-center w-50">
                            <h6 class="value-title">Dispos</h6>
                            <h5>
                                <MDBSpinner size="sm" v-if="isLoading"/>
                                <span v-else-if="dispoStatus === null">-</span>
                                <i v-else-if="dispoStatus" class="fas fa-check text-success"></i>
                                <i v-else class="fas fa-times text-danger"></i>
                            </h5>
                        </div>
                    </div>
                </MDBCardText>
            </MDBCardBody>
        </MDBCard>
    </MDBCol>
</template>

<script setup>
import {
    MDBCard,
    MDBCardBody,
    MDBCardText,
    MDBCardTitle,
    MDBCol,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import {formatNumber} from "@/helpers";

const props = defineProps({
    hoursStatus: {
        type: [Boolean, null],
        required: true,
    },
    dispoStatus: {
        type: [Boolean, null],
        required: true,
    },
    day: {
        type: String,
        required: true,
    },
    isLoading: {
        type: Boolean,
        required: true,
    },
});
</script>
<style lang="scss">
.not-applicable {
    background-color: lightgray;
}

.value-title {
    font-size: 0.75em;
    margin: 0;
}
</style>
