<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId } from 'vue';

import { setMagicIcon } from '../apiCall';
import { apiFile } from '../apiCall';

import TopMenuFileEditor from './component/TopMenuFileEditor.vue';
import AceFileEditor from './component/AceFileEditor.vue';
import FileManager from '../CommonCom/FileManager.vue';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

const showFileManager = ref(false);
const fileName = ref('');
const fileData = ref('1234');
const fileExtention = ref('');
const lang = ref('html');

watch(fileName, async (val) => {
  if (val == '') return;
  const res = await apiFile({ command: 'loadFile', fileName: val });
  fileData.value = res.fileData;
  fileExtention.value = val.split('.').pop();
  lang.value = val.split('.').pop();
  console.log(lang.value);
});

async function saveFile() {
  if (fileName.value == '') {
    document.showToast('Нет имени');
    return;
  }
  const res = await apiFile({
    command: 'saveFile',
    fileName: fileName.value,
    fileData: fileData.value,
  });
  document.showToast('Сохранено');
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

// определение размера окна редакторов
const ready = ref({
  show: false,
  x: 0,
  y: 0,
});

onMounted(() => {
  const rect = document.getElementById('editor-layer').getBoundingClientRect();
  ready.value.y = window.innerHeight - rect.top; // расстояние до нижнего края экрана
  ready.value.x = window.innerWidth; // Ширина окна
  ready.value.show = true;
  fileName.value = decodeURIComponent(location.search.slice(1));
  // fileName.value = '/design/test.css';
  window.addEventListener('keydown', handleKeydown);
});
onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});

const handleKeydown = (event) => {
  if (event.ctrlKey && event.code === 'KeyS') {
    event.preventDefault(); // Prevent browser's default save action
    saveFile();
    return;
  }
};
</script>

<template>
  <TopMenuFileEditor v-model:visible="showFileManager" :saveFile="saveFile" :formatFile="formatFile"></TopMenuFileEditor>
  <div :style="{ height: ready.y + 'px', width: ready.x + 'px' }" id="editor-layer">
    <div v-if="ready.show">
      <Splitter :style="{ height: ready.y + 'px', width: ready.x + 'px' }">
        <SplitterPanel :size="30" :minSize="10">
          <div style="position: relative">
            <button
              show="store.statusLeftPannel === 'tree'"
              style="position: absolute; right: 10px; z-index: 1000"
              class="btn btn-primary fas fa-search"
              click="store.statusLeftPannel = 'find'"
            ></button>
          </div>
          Дерево
        </SplitterPanel>
        <SplitterPanel :size="70" :minSize="20">
          <AceFileEditor v-model="fileData" :fileExtention="fileExtention" theme="chrome" :height="ready.y" />
        </SplitterPanel>
      </Splitter>
    </div>
  </div>
  <FileManager v-model:visible="showFileManager"></FileManager>
  <!-- тосты -->
  <TosatConfirm></TosatConfirm>
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
