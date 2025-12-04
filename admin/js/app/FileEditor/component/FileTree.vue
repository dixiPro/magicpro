<script setup>
import { ref, computed, onMounted, watch, reactive } from 'vue';

import { useFileStore } from '../storeFileEditor';
const store = useFileStore();

const props = defineProps({
  fileName: { type: String, default: '' },
});

const changeFile = defineEmits(['changeFile']);
const back = [];
const currentDirectory = ref('');
const directoryList = ref([]);

// const tmp = currentFile.value ? currentFile.value : startDirectory.value + 'f.f';
// path.value = tmp.split('/').slice(0, -1);

async function loadDirectory() {
  const cp = currentDirectory.value;
  if (cp == '') return;
  console.log(cp);
  directoryList.value = await apiFile({
    command: 'dirList',
    path: addSlashes(cp),
  });
}

onMounted(async () => {
  const start = await apiFile({ command: 'start' });
  currentDirectory.value = start.startDirectory;
  console.log(start);
  await loadDirectory();
});

function process(key) {
  const el = directoryList.value[key];
  if (el.mime === 'directory') currentDirectory.value = currentDirectory.value + '/' + el.name;
}

function getPath(filename) {
  filename = filename.replace(/\/{2,}/g, '/');
  const parts = filename.split('/');
  const last = parts.at(-1);
  if (!last.includes('.')) return filename;
  return parts.slice(0, -1).join('/');
}

function addSlashes(name) {
  name = '/' + name + '/';
  name = name.replace(/\/{2,}/g, '/');
  console.log(name);
  return name;
}

watch(currentDirectory, async (val) => {
  await loadDirectory();
});
</script>

<template>
  <div class="ms-2 tree-pannel">
    <div>currentDirectory =={{ currentDirectory }}==</div>
    <div v-for="(val, key) in directoryList" :key="key">
      <div><span v-text="val.name" @click="process(key)"></span></div>
    </div>
    <pre>path {{ JSON.stringify(directoryList, null, 2) }} </pre>
  </div>
</template>
<style></style>
