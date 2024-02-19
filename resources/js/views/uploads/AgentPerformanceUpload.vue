<template>
    <h2>Agent Hours Upload</h2>
    <file-uploader api-endpoint="uploads/agent-performance"></file-uploader>
</template>

<script>
import FileUploader from "@/components/FileUploader.vue";

export default {
    components: {
        FileUploader,
    },
}
</script>
