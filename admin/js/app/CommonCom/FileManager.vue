<script setup>
//
// uploadFile если файл уже существует?
//
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick } from 'vue';
import { apiFile, getFileExtension } from '../apiCall';

import UploadFile from './UploadFile.vue';
import FileItem from './FileItem.vue';
import EditFile from './EditFile.vue';
import ModalWindow from './ModalWindow.vue';

const showFileManager = defineModel('visible', { type: Boolean, default: false });

const uploadRef = ref(null);

//
const startDirectory = ref('/design/');
const path = ref('/design/');
path.value = startDirectory.value;
const directory = ref([]);
const backToRoot = ref([]);

const viewFull = ref(true);
const viewSize = ref(160);

const ready = ref(false);
// добавить папку
const showNewFolderModal = ref(false);
const newFolderName = ref('');

// переименовать
const showRenameModal = ref(false);

onMounted(async () => {
  await start(path.value);
  await openFolder(path.value);
});

async function goBack() {
  if (backToRoot.value.length > 0) {
    path.value = backToRoot.value.pop();
  }
  await openFolder(path.value);
}

function openNewFolderModal() {
  newFolderName.value = '';
  showNewFolderModal.value = true;
}

async function start() {
  try {
    ready.value = false;
    const res = await apiFile({ command: 'start' });
    startDirectory.value = res.startDirectory;
    path.value = res.startDirectory;
  } catch (e) {
    console.error(e);
  } finally {
    ready.value = true;
  }
}

async function createFolder() {
  if (!newFolderName.value.trim()) return;

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

function uploadFile() {
  uploadRef.value.open();
}

async function changeFolder(newPath) {
  try {
    ready.value = false;
    backToRoot.value.push(path.value);
    path.value = path.value + newPath + '/';
    await openFolder(path.value);
  } catch (e) {
    console.error(e);
  } finally {
    ready.value = true;
  }
}

function onFileUploaded(fileInfo) {
  document.showToast('Загружен ' + fileInfo.name);
  // удаляем файл если был такой
  directory.value = directory.value.filter((item) => item.name !== fileInfo.name);
  directory.value.unshift(fileInfo);
  // fileI.nfo = { uploaded: true, name, path, mime, size }
  // тут можно обновить список файлов
}

async function openFolder(pathVal) {
  try {
    ready.value = false;
    directory.value = await apiFile({
      command: 'dirList',
      path: pathVal,
    });
  } catch (error) {
    console.error(error);
  } finally {
    ready.value = true;
  }
}

async function deleteFileOrFolder(name) {
  if (!(await document.confirmDialog('Удалить?' + path.value + name))) return;
  try {
    ready.value = false;

    await apiFile({
      command: 'delete',
      path: path.value,
      name: name,
    });
    directory.value = directory.value.filter((item) => item.name !== name);
    document.showToast('Удален ' + name);
  } catch (e) {
    console.error(e);
  } finally {
    ready.value = true;
  }
}

function copyClipBoard(fullLink) {
  const textarea = document.createElement('textarea');
  textarea.value = fullLink;
  textarea.style.position = 'fixed';
  textarea.style.opacity = '0';
  document.body.appendChild(textarea);
  textarea.select();
  textarea.setSelectionRange(0, textarea.value.length); // для мобильных
  try {
    const ok = document.execCommand('copy');
    document.showToast(ok ? 'Скопирована ссылка ' + fullLink : 'Ошибка копирования ссылки', ok ? 'success' : 'error');
  } catch (e) {
    document.showToast('Ошибка копирования ссылки', 'error');
  } finally {
    document.body.removeChild(textarea);
  }
}

function copyImg(el) {
  const s = `<img src="${encodeURI(path.value + el.name)}" alt="${el.name}" height="${el.height}" width="${el.width}" />`;
  copyClipBoard(s);
}

function copyLink(el) {
  copyClipBoard(encodeURI(path.value + el.name));
}

function externalEdit(el) {
  fileName.value = path.value + el.name;
  editFileStatus.value = true;
}

const menu = ref();
const items = ref([]);

const stopEscPropagation = (e) => {
  if (e.key === 'Escape') {
    e.stopPropagation();
    if (menu.value?.hide) {
      menu.value.hide();
    }

    document.removeEventListener('keydown', stopEscPropagation, true);
  }
};

function onRightClick(event, el) {
  // menu.value.hide();
  document.addEventListener('keydown', stopEscPropagation, true);
  items.value = [
    {
      label: 'Удалить',
      icon: 'fas fa-trash',
      command: () => {
        deleteFileOrFolder(el.name);
      },
    },

    {
      label: 'Переименовать',
      icon: 'fas fa-plus',
      command: () => {},
    },
  ];

  if (el.type === 'file') {
    items.value.push({
      label: 'Копировать путь',
      icon: 'fas fa-link',
      command: () => {
        copyLink(el);
      },
    });
  }

  if (el.type === 'file' && el.isImage) {
    items.value.push({
      label: 'Копировать Img',
      icon: 'far fa-file-image',
      command: () => {
        copyImg(el);
      },
    });
  }

  if (el.type === 'file') {
    if (['css', 'js', 'ts', 'txt', 'csv', 'json', 'md'].includes(getFileExtension(el.name))) {
      items.value.push({
        label: 'Редактировать',
        icon: 'fas fa-pen',
        command: () => {
          externalEdit(el);
        },
      });
    }
  }

  // показ меню
  menu.value.show(event);
}

async function navBackTo(index) {
  // В самом начале
  if (backToRoot.value.length === 0) {
    return;
  }
  // перейти в начало
  if (index === 0) {
    path.value = startDirectory.value;
    backToRoot.value = [];
    await openFolder(path.value);
    return;
  }
  // стоим в конеце
  if (index >= backToRoot.value.length) {
    return;
  }
  // середина
  path.value = backToRoot.value[index];
  backToRoot.value = backToRoot.value.slice(0, index);
  await openFolder(path.value);
}

const backPathArr = computed(() => {
  const arr1 = path.value.split('/').filter((el) => el !== '');
  const arr = arr1.map((el, index) => {
    return {
      name: el,
      index: index,
    };
  });
  return arr;
});

const fileName = ref('');
const editFileStatus = ref(false);
</script>

<template>
  <!-- <ModalWindow v-model:visible="showFileManager" modal position="top" class="w-75 mt-4"> -->

  <ModalWindow v-model:visible="showFileManager" :zIndex="1000">
    <template #header>
      <div class="d-flex align-items-center gap-2 p-1 rounded w-100 border">
        <button v-if="path !== startDirectory" @click="goBack" class="btn btn-success fas fa-chevron-left"></button>
        <button @click="openNewFolderModal" class="btn btn-success fas fa-folder-plus"></button>
        <button @click="uploadFile" class="btn btn-success fas fa-file-upload"></button>

        <div class="flex-grow-1">
          <span v-for="(el, i) in backPathArr" :key="i">
            <span
              class="path-element"
              :class="{
                'path-end': i === backPathArr.length - 1,
              }"
              @click="navBackTo(i)"
              >{{ el.name }}</span
            ><span class="mx-1" style="font-size: 80%">/</span></span
          >
        </div>

        <div>
          <button
            class="btn btn-success"
            :class="{
              'fas fa-list': viewFull,
              'fas fa-th': !viewFull,
            }"
            @click="viewFull = !viewFull"
          ></button>
        </div>
      </div>
    </template>

    <div v-if="ready" class="mt-2">
      <!-- Пусто -->
      <div v-if="directory.length === 0" class="my-5 py-5 text-center">...</div>

      <!-- Папки -->
      <div v-for="(el, index) in directory.filter((e) => e.type === 'dir')">
        <div class="d-flex folder">
          <div class="me-2">
            <i class="far fa-folder pointer" @click="changeFolder(el.name)"></i>
          </div>
          <div class="pointer" @contextmenu.prevent="onRightClick($event, el)" @click="changeFolder(el.name)">
            {{ el.name }}
          </div>
        </div>
      </div>
      <!-- файлы -->
      <div :class="{ 'd-flex flex-wrap': viewFull }" class="">
        <FileItem
          v-for="el in directory.filter((e) => e.type !== 'dir')"
          :key="el.name"
          :el="el"
          :viewFull="viewFull"
          :viewSize="viewSize"
          :path="path"
          :onRightClick="onRightClick"
        />
      </div>
    </div>
    <div v-else class="text-center p-3">
      <div class="spinner-border text-info"></div>
    </div>
  </ModalWindow>

  <EditFile :fileName="fileName" :zIndex="1000" v-if="editFileStatus" @close="editFileStatus = false"></EditFile>

  <!-- Модалка создания папки -->
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

  <!-- Модалка переименовать-->
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

  <UploadFile ref="uploadRef" :path="path" :directory="directory" @uploaded="onFileUploaded" class="w-50" />

  <ContextMenu ref="menu" :model="items" />
</template>
<style>
.path-element {
  cursor: pointer;
  /* text-decoration: underline; */
  box-shadow: inset 0 -1px 0 #bbb;
}

.path-element:hover {
  color: #198754;
  box-shadow: inset 0 -1px 0 #198754;
}

.path-end {
  font-weight: bold;
}

.folder {
  color: var(--bs-indigo);
}
</style>
