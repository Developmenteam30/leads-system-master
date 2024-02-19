<template>
    <h2>Job Queue</h2>
    <MDBContainer class="mt-3 p-0 m-0">
        <MDBRow>
            <MDBCol col="4">
                <MDBBtn color="primary" @click="getReport">Refresh
                    <MDBSpinner tag="span" size="sm" v-if="isLoading" class="ms-2"/>
                </MDBBtn>
            </MDBCol>
        </MDBRow>
    </MDBContainer>
    <MDBAlert color="danger" static v-if="error">{{ error }}</MDBAlert>
    <MDBDatatable
        :dataset="dataset"
        :loading="isLoading"
        :entries="50"
        striped
        fixedHeader
        class="mt-3"
        @render="setActions"
    />
</template>

<script>
import {MDBAlert, MDBBtn, MDBCol, MDBContainer, MDBDatatable, MDBDatepicker, MDBRow, MDBSelect, MDBSpinner} from "mdb-vue-ui-kit";
import apiClient from "../http";
import SmartSelect from "@/components/SmartSelect.vue";
import {toast} from "vue3-toastify";

export default {
    components: {
        MDBAlert,
        MDBBtn,
        MDBCol,
        MDBContainer,
        MDBDatatable,
        MDBDatepicker,
        MDBRow,
        MDBSelect,
        MDBSpinner,
        SmartSelect,
    },

    mounted() {
        this.getReport();
    },

    data() {
        return {
            isLoading: false,
            error: '',
            dataset: {
                columns: [
                    {label: "Timestamp", field: "timestamp"},
                    {label: "User", field: "agent_name"},
                    {label: "Action", field: "action"},
                    {label: "File Name", field: "file"},
                    {label: "File Date", field: "file_date"},
                    {label: "Success", field: "success"},
                    {label: "Message", field: "message"},
                    {label: "Retry", field: "retry"},
                ],
                rows: []
            },
        };
    },

    methods: {
        getReport() {
            this.isLoading = true;
            this.error = '';

            apiClient.get('job-queue')
                .then(({data}) => {
                    this.dataset.rows = data;
                }).catch(error => {
                if (error.response && error.response.data && error.response.data.message) {
                    this.error = error.response.data.message;
                } else {
                    this.error = error;
                }
            }).finally(() => {
                this.isLoading = false;
            });
        },

        setActions() {
            Array.from(document.getElementsByClassName("retry-btn")).forEach(btn => {
                if (btn.getAttribute("click-listener") !== "true") {
                    btn.addEventListener("click", () => this.retryJob(btn.attributes["data-log-id"].value));
                    btn.setAttribute("click-listener", "true");
                }
            });
        },

        retryJob(logId) {
            this.isLoading = true;
            this.error = '';

            apiClient.post('job-queue/retry/' + logId)
                .then(({data}) => {
                    toast.success("The job has been re-submitted.");
                    this.getReport();
                }).catch(error => {
            }).finally(() => {
                this.isLoading = false;
            });
        },
    },
}
</script>
