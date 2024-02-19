<template>
    <h2>Disposition Log Upload</h2>
    <p>Fields needed for this report: call_id, status_name, time_stamp, agent_name, bill_time, agent_id, status</p>
    <file-uploader api-endpoint="uploads/call-detail-log"></file-uploader>
</template>

<script>
import FileUploader from "@/components/FileUploader.vue";

export default {
    components: {
        FileUploader,
    },
}
</script>
