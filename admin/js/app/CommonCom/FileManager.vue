<script setup>
//
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick } from 'vue';
import { apiFile, getFileExtension, copyClipBoard } from '../apiCall';

import UploadFile from './UploadFile.vue';
import FileItem from './FileItem.vue';
import ModalWindow from './ModalWindow.vue';
import EditFile from '../CommonCom/EditFile.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

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

// add folder
const modalAddNewFolder = reactive({
  visible: false,
  newFolderName: '',
});

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
  modalAddNewFolder.newFolderName = '';
  modalAddNewFolder.visible = true;
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

async function deleteFileOrFolder(name) {
  if (!(await document.confirmDialog(t('delete') + ' ' + path.value + name))) return;
  try {
    ready.value = false;
    await apiFile({
      command: 'delete',
      deleteFile: path.value + name,
    });
    directory.value = directory.value.filter((item) => item.name !== name);
    document.showToast(t('was_deleted') + ' ' + name);
  } catch (e) {
    console.error(e);
  } finally {
    ready.value = true;
  }
}

async function createFolder() {
  // empty
  if (!modalAddNewFolder.newFolderName.trim()) return;
  try {
    ready.value = false;

    await apiFile({
      command: 'mkdir',
      folderName: path.value + modalAddNewFolder.newFolderName.trim(),
    });
    await openFolder(path.value);
  } catch (e) {
    console.error(e);
  } finally {
    modalAddNewFolder.visible = false;
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
  document.showToast(t('file_was_loaded') + ' ' + fileInfo.name);
  //del file from list
  directory.value = directory.value.filter((item) => item.name !== fileInfo.name);
  directory.value.unshift(fileInfo);
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

function copyImg(el) {
  const s = `<img src="${encodeURI(path.value + el.name)}" alt="${el.name}" height="${el.height}" width="${el.width}" />`;
  copyClipBoard(s);
}

function copyLink(el) {
  copyClipBoard(encodeURI(path.value + el.name));
}

const editFile = reactive({
  visible: false,
  fileName: '',
  edit: function (el) {
    this.fileName = path.value + el.name;
    this.visible = true;
  },
});

// const editFileStatus = ref(true);
// const fileName = ref(path.value + '_snippetsBlade.js');
// function externalEdit(el) {
//   editFileStatus.value = true;
//   modalRename.fileName = path.value + el.name;
// }

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

function onRightClick(event, el, index) {
  // menu.value.hide();
  document.addEventListener('keydown', stopEscPropagation, true);
  items.value = [];

  if (el.type === 'file') {
    items.value.push({
      label: t('copy_path'),
      icon: 'fas fa-link',
      command: () => {
        copyLink(el);
      },
    });
  }

  if (el.type === 'file' && el.isImage) {
    items.value.push({
      label: t('copy_as_imgcode'),
      icon: 'far fa-file-image',
      command: () => {
        copyImg(el);
      },
    });
  }

  if (el.type === 'file') {
    if (['css', 'js', 'ts', 'txt', 'csv', 'json', 'md'].includes(getFileExtension(el.name))) {
      items.value.push({
        label: t('edit'),
        icon: 'fas fa-pen',
        command: () => {
          editFile.edit(el);
        },
      });
    }
  }

  items.value.push({
    label: t('rename'),
    icon: 'fas fa-plus',
    command: () => {
      modalRename.visible = true;
      modalRename.newName = el.name;
      modalRename.oldName = el.name;
    },
  });

  items.value.push({
    label: t('delete'),
    icon: 'fas fa-trash',
    command: () => {
      deleteFileOrFolder(el.name);
    },
  });

  // show menu
  menu.value.show(event);
}

async function navBackTo(index) {
  // at home now
  if (backToRoot.value.length === 0) {
    return;
  }
  // goto root
  if (index === 0) {
    path.value = startDirectory.value;
    backToRoot.value = [];
    await openFolder(path.value);
    return;
  }
  // tail
  if (index >= backToRoot.value.length) {
    return;
  }
  // middle
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

const modalRename = reactive({
  visible: false,
  oldName: '',
  newName: '',
  rename: async function () {
    try {
      ready.value = false;
      await apiFile({
        command: 'rename',
        oldName: path.value + this.oldName,
        newName: path.value + this.newName,
      });
      directory.value = directory.value.map((item) => {
        if (item.name == this.oldName) {
          item.name = this.newName;
        }
        return item;
      });
      this.visible = false;
      document.showToast(t('ready'));
    } catch (e) {
      console.error(e);
    } finally {
      ready.value = true;
    }
  },
});
</script>

<template>
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
      <!-- Empty -->
      <div v-if="directory.length === 0" class="my-5 py-5 text-center">...</div>

      <!-- FOlders -->
      <div v-for="(el, index) in directory.filter((e) => e.type === 'dir')">
        <div class="d-flex folder">
          <div class="me-2">
            <i class="far fa-folder pointer" @click="changeFolder(el.name)"></i>
          </div>
          <div class="pointer" @contextmenu.prevent="onRightClick($event, el, index)" @click="changeFolder(el.name)">
            {{ el.name }}
          </div>
        </div>
      </div>
      <!-- files -->
      <div :class="{ 'd-flex flex-wrap': viewFull }" class="">
        <FileItem
          @contextmenu.prevent="onRightClick($event, el, index)"
          v-for="el in directory.filter((e) => e.type !== 'dir')"
          :key="el.name"
          :el="el"
          :viewFull="viewFull"
          :viewSize="viewSize"
          :path="path"
        />
      </div>
    </div>
    <div v-else class="text-center p-3">
      <div class="spinner-border text-info"></div>
    </div>
  </ModalWindow>

  <!-- ModalWindow create folder-->
  <Dialog v-model:visible="modalAddNewFolder.visible" :header="t('create_folder')" modal>
    <div class="mb-3">
      <label class="form-label">{{ t('name_folder') }}</label>
      <input v-model="modalAddNewFolder.newFolderName" type="text" class="form-control" :placeholder="t('name_folder')" @keyup.enter="createFolder" />
    </div>
    <template #footer>
      <button class="btn btn-secondary btn-sm" @click="modalAddNewFolder.visible = false">{{ t('cancel') }}</button>
      <button class="btn btn-success btn-sm" @click="createFolder">{{ t('create') }}</button>
    </template>
  </Dialog>

  <!-- ModalWindow rename-->
  <Dialog v-model:visible="modalRename.visible" :header="t('rename')" modal>
    <div class="mb-3">
      <input v-model="modalRename.newName" type="text" class="form-control" :placeholder="t('new_name')" @keyup.enter="modalRename.rename" />
    </div>
    <template #footer>
      <button class="btn btn-secondary btn-sm" @click="modalRename.visible = false">{{ t('cancel') }}</button>
      <button class="btn btn-success btn-sm" @click="modalRename.rename">{{ t('submit') }}</button>
    </template>
  </Dialog>

  <UploadFile ref="uploadRef" :path="path" :directory="directory" @uploaded="onFileUploaded" class="w-50" />

  <ContextMenu ref="menu" :model="items" />

  <EditFile :fileName="editFile.fileName" :zIndex="1000" v-if="editFile.visible" @close="editFile.visible = false"></EditFile>
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
