// stores/article.js
import { defineStore } from 'pinia';
import { ref, toRaw, computed } from 'vue';
import { apiArt, translitString } from '../apiCall';
export const useFileStore = defineStore('fileStore', () => {
  // data
  const backPath = ref([]);
  const currentDirectory = ref('');
  const directoryList = ref([]);
  const currentFile = ref('');

  async function loadDirectory(name) {
    const start = await apiFile({ command: 'start' });
    currentDirectory.value = start.startDirectory;
    console.log(start);

    const cp = currentDirectory.value;
    if (cp == '') return;
    console.log(cp);
    directoryList.value = await apiFile({
      command: 'dirList',
      path: addSlashes(cp),
    });
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

  return {
    backPath,
    currentDirectory,
    directoryList,
    currentFile,
    loadDirectory,
  };
});
