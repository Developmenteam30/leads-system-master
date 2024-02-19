<template>
    <h2>Retreaver Upload</h2>
    <p>Fields needed for this report: Timestamp, PublisherName, TotalDurationSecs</p>
    <file-uploader
        api-endpoint="uploads/retreaver"
    ></file-uploader>
</template>

<script>
import FileUploader from "@/components/FileUploader.vue";

export default {
    components: {
        FileUploader,
    },
}
</script>
