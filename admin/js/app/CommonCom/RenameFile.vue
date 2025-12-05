<script setup>
//
// uploadFile если файл уже существует?
//
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick } from 'vue';
import { apiFile, getFileExtension } from '../apiCall';

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
  // await apiFile({
  //   command: 'rename',
  //   oldName: props.path + fileName.value,
  //   newName: props.path + newName.value,
  // });
  visible.value = false;
  fileName.value = newName.value;
}
</script>

<template>
  <!-- Модалка переименовывания -->
  <Dialog v-model:visible="visible" style="width: 400px" header="Переименовать" modal>
    <div class="row">
      <div class="col-12 my-2">
        <input type="text" class="form-control form-control-sm" v-model="newName" />
      </div>
    </div>
    <button class="btn btn-sm btn-success" @click="rename">Сохранить</button>
  </Dialog>
</template>
<style></style>
