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

async function rename() {
  try {
    ready.value = false;

    await apiFile({
      command: 'mkdir',
      path: path.value,
      name: newFolderName.value.trim(),
    });
    await openFolder(path.value);
  } catch (e) {
    console.error(e);
  } finally {
    showNewFolderModal.value = false;
    ready.value = true;
  }
}
</script>

<template>
  <!-- Модалка переименовывания -->
  <Dialog v-model:visible="showNewFolderModal" header="Создать папку" modal>
    <div class="mb-3">
      <label class="form-label">Имя новой папки</label>
      <input v-model="newFolderName" type="text" class="form-control" placeholder="Новая папка" @keyup.enter="createFolder" />
    </div>
    <template #footer>
      <button class="btn btn-secondary btn-sm" @click="showNewFolderModal = false">Отмена</button>
      <button class="btn btn-success btn-sm" @click="createFolder">Создать</button>
    </template>
  </Dialog>
</template>
<style></style>
