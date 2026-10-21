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
</script>

<template>
  <FileManager bare></FileManager>
  <TosatConfirm></TosatConfirm>
</template>
