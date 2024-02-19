<template>
  <AbstractList
      ajax-url="notification-type/manage"
      title-singular="Notification Type"
      title-plural="Notification Types"
      :form="form"
      @update:form="(value) => form = value"
  >
      <template v-slot:edit-modal>
          <MDBAlert v-if="form.id" color="warning" static class="mb-4"><i class="fas fa-exclamation-triangle me-3"></i> Use caution when editing an existing slug as this may affect code that is already deployed.</MDBAlert>

          <MDBInput label="Slug" v-model="form.slug" :maxlength=255 :required="true"/>

          <MDBInput label="Description" v-model="form.description" />
      </template>
  </AbstractList>
</template>

<script setup>
import AbstractList from "@/components/AbstractList.vue";
import {MDBAlert, MDBInput} from "mdb-vue-ui-kit";
import {ref} from "vue";

const form = ref({
  id: null,
});
</script>
