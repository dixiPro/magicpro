<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId } from 'vue';
import { apiFile } from '../apiCall';

import ModalWindow from './ModalWindow.vue';
import AceFileEditor from './AceFileEditor.vue';

const props = defineProps({
  fileName: { type: String, default: '/design/1.txt' },
  width: { type: String, default: '800' },
  height: { type: String, default: '600' },
});

const fileExtention = ref('');
const fileData = ref('');
const ready = ref(false);

const handleKeydown = async (event) => {
  if (event.ctrlKey && event.code === 'KeyS') {
    console.log('save file');
    event.stopImmediatePropagation();
    event.preventDefault(); // Prevent browser's default save action
    event.stopPropagation();
    await saveFile();
  }
};

onMounted(async () => {
  fileExtention.value = props.fileName.split('.').pop();
  const res = await apiFile({ command: 'loadFile', fileName: props.fileName });
  fileData.value = res.fileData;
  ready.value = true;
  window.addEventListener('keydown', handleKeydown, { capture: true });
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});

const ms = ref(true);

async function saveFile() {
  console.log(fileData.value);
  const res = await apiFile({
    command: 'saveFile',
    fileName: props.fileName,
    fileData: fileData.value,
  });
  document.showToast('Сохранен файл ' + props.fileName);
}

async function formatFile() {
  let formatted = '';

  switch (fileExtention.value) {
    case 'css': {
      formatted = await prettier.format(fileData.value, {
        parser: 'css',
        plugins: [prettierPlugins.postcss],
        tabWidth: 4,
        printWidth: 160,
      });
      break;
    }
    case 'js': {
      formatted = await prettier.format(fileData.value, {
        parser: 'babel',
        plugins: prettierPlugins,
        semi: true,
        tabWidth: 4,
        printWidth: 120,
      });
      break;
    }
    default: {
      return;
    }
  }
  document.showToast('Отформатировано');
  fileData.value = formatted;
}

const emit = defineEmits(['close']);
</script>

<template>
  <ModalWindow
    v-model:visible="ms"
    width="props.width"
    height="props.height"
    header="Имя файла"
    v-if="ready"
    @close="
      emit('close');
      ready = false;
    "
  >
    <template #header>
      <div class="d-flex gx-2 py-2 mx-0 align-items-center">
        <div class="flex-grow-1">
          {{ fileName }}
        </div>
        <div class="mx-2">
          <button class="btn btn-sm btn-primary" @click="saveFile">Save→</button>
        </div>

        <div class="">
          <button class="btn fas fa-magic btn-success" @click="formatFile"></button>
        </div>
      </div>
    </template>
    <AceFileEditor v-model="fileData" :fileExtention="fileExtention" theme="chrome" />
  </ModalWindow>
</template>

<style>
.pointer {
  cursor: pointer;
}

.icon-menue {
  cursor: pointer;
  font-size: 28px;
}

.icon-border {
  border: 1px solid #777;
}
:root {
  --p-dialog-header-padding: 0.2rem 1rem;
  /* новое значение */
}

.fas,
.far {
  cursor: pointer;
}

.small {
  font-size: 0.8em;
}
.tree-pannel {
  overflow-y: auto;
  height: 95%;
  overflow-x: hidden;
  white-space: nowrap;
}
</style>
