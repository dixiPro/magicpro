<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId } from 'vue';
import { apiFile } from '../apiCall';

import EditFile from '../CommonCom/EditFile.vue';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

const editFileStatus = ref(true);
const fileName = ref('/design/css/common.css');
</script>

<template>
  <EditFile :fileName="fileName" :zIndex="1000" v-if="editFileStatus" @close="editFileStatus = false"></EditFile>
  <TosatConfirm></TosatConfirm>
</template>
