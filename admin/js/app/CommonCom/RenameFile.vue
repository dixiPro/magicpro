<script setup>
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick } from 'vue';
import { apiFile, getFileExtension } from '../apiCall';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const visible = defineModel('visible');
const fileName = defineModel('fileName');

const props = defineProps({
  path: { type: String, require: true },
});

const newName = ref('');
onMounted(() => {
  newName.value = fileName.value;
});
async function rename() {
  visible.value = false;
  fileName.value = newName.value;
}
</script>

<template>
  <!-- Модалка переименовывания -->
  <Dialog v-model:visible="visible" style="width: 400px" :header="t('rename')" modal>
    <div class="row">
      <div class="col-12 my-2">
        <input type="text" class="form-control form-control-sm" v-model="newName" />
      </div>
    </div>
    <button class="btn btn-sm btn-success" @click="rename">{{ t('save') }}</button>
  </Dialog>
</template>
<style></style>
