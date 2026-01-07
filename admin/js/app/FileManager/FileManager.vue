<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId } from 'vue';
import FileManager from '../CommonCom/FileManager.vue';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

onMounted(() => {
  // const a = showSpinner();
  // setTimeout(() => {
  //   spinnerHide(a);
  // }, 3000);
});
const visible = ref(true);
</script>

<template>
  <div v-if="!visible" class="my-5" @click="visible = !visible"><button class="btn btn-sm btn-primary">show filemanager</button></div>
  <FileManager v-model:visible="visible"></FileManager>
  <TosatConfirm></TosatConfirm>
</template>
