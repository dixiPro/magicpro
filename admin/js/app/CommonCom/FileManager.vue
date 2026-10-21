<script setup>
//
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick } from 'vue';
import { apiFile, copyClipBoard } from '../apiCall';

import UploadFile from './UploadFile.vue';
import UploadImage from './UploadImage.vue';
import MediaView from './MediaView.vue';
import MoveFiles from './MoveFiles.vue';
import { mediaKind, isEditable } from './fileKinds.js';
import FileItem from './FileItem.vue';
import ModalWindow from './ModalWindow.vue';
import FullWindow from './FullWindow.vue';
import EditFile from '../CommonCom/EditFile.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const showFileManager = defineModel('visible', { type: Boolean, default: false });

// bare: the page of its own (/a_dmin/fileManager) — the whole window, no modal
const props = defineProps({
  bare: { type: Boolean, default: false },
});

const uploadRef = ref(null);
const uploadImageRef = ref(null);

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

// add file
const modalAddNewFile = reactive({
  visible: false,
  fileName: '',
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

function openNewFileModal() {
  modalAddNewFile.fileName = '';
  modalAddNewFile.visible = true;
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
  const msg = t('delete') + ' ' + path.value + name;
  if (!(await document.confirmDialog(msg))) return;
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

async function createFile() {
  // empty

  if (!modalAddNewFile.fileName.trim()) return;
  try {
    ready.value = false;

    await apiFile({
      command: 'mkfile',
      fileName: path.value + modalAddNewFile.fileName.trim(),
    });
    await openFolder(path.value);
  } catch (e) {
    console.error(e);
  } finally {
    modalAddNewFile.visible = false;
    ready.value = true;
  }
}

function uploadFile() {
  uploadRef.value.open();
}

// a picture is cropped and tuned in MagicImage before it lands in the folder
function uploadImage() {
  uploadImageRef.value.open();
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

// group mode: checkboxes on every item and one action over the chosen ones
const groupMode = ref(false);
const selected = ref([]);

function toggleGroupMode() {
  groupMode.value = !groupMode.value;
  selected.value = [];
}

function toggleSelected(name) {
  selected.value = selected.value.includes(name) ? selected.value.filter((it) => it !== name) : [...selected.value, name];
}

// the checkbox of the panel: all or nothing, folders counted with the files
const allSelected = computed(() => directory.value.length > 0 && selected.value.length === directory.value.length);

function toggleAll() {
  selected.value = allSelected.value ? [] : directory.value.map((el) => el.name);
}

/**
 * Deletes everything that is ticked. One question for the whole lot — the
 * names are in front of the person on the screen — and the answer of the
 * server decides every name on its own: what went, goes off the list.
 */
async function deleteSelected() {
  if (selected.value.length === 0) return;

  const msg = t('delete') + ' ' + selected.value.length + ': ' + selected.value.join(', ');

  if (!(await document.confirmDialog(msg))) return;

  ready.value = false;

  const gone = [];

  for (const name of selected.value) {
    try {
      await apiFile({ command: 'delete', deleteFile: path.value + name });
      gone.push(name);
    } catch (e) {
      console.error(e);
      document.showToast(t('delete') + ' ' + name + ': ' + (e?.message ?? ''), 'error');
    }
  }

  directory.value = directory.value.filter((item) => !gone.includes(item.name));
  selected.value = [];
  groupMode.value = false;
  ready.value = true;

  if (gone.length) document.showToast(t('was_deleted') + ' ' + gone.length);
}

// moving: the window picks the folder, the names come from here — one from the
// context menu or everything ticked in group mode
const moveFiles = reactive({
  visible: false,
  names: [],
  open(names) {
    this.names = names;
    this.visible = true;
  },
});

function onMoved(gone) {
  directory.value = directory.value.filter((item) => !gone.includes(item.name));
  selected.value = [];
  groupMode.value = false;
}

// a look at one media file: the window lives next to the manager and gets the
// item it shows
const mediaFile = reactive({
  visible: false,
  el: null,
  kind: '',
});

/**
 * A click on a file: media goes to the window, text to the editor, and
 * everything else says so — an archive or a psd has nothing to show here.
 */
function openFile(el) {
  if (el.type === 'dir') return;

  if (groupMode.value) {
    toggleSelected(el.name);

    return;
  }

  const kind = mediaKind(el.name);

  if (kind) {
    mediaFile.el = el;
    mediaFile.kind = kind;
    mediaFile.visible = true;

    return;
  }

  if (isEditable(el.name)) {
    editFile.edit(el);

    return;
  }

  document.showToast(t('nothing_to_open'));
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
    if (isEditable(el.name)) {
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
    label: t('move'),
    icon: 'fas fa-arrow-right',
    command: () => {
      moveFiles.open([el.name]);
    },
  });

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
  <component :is="props.bare ? FullWindow : ModalWindow" v-model:visible="showFileManager" :zIndex="1000">
    <template #header>
      <div class="d-flex align-items-center gap-2 p-1 rounded w-100 border">
        <button v-if="path !== startDirectory" @click="goBack" class="btn btn-success fas fa-chevron-left"></button>

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
          <button @click="openNewFileModal" class="me-2 btn btn-success fas fa-plus-circle"></button>
          <button @click="openNewFolderModal" class="me-2 btn btn-success fas fa-folder-plus"></button>
          <button @click="uploadFile" class="me-2 btn btn-success fas fa-file-upload"></button>
          <button @click="uploadImage" class="me-2 btn btn-success fas fa-image" :title="t('upload_image')"></button>

          <button
            class="btn btn-success"
            :class="{
              'fas fa-list': viewFull,
              'fas fa-th': !viewFull,
            }"
            @click="viewFull = !viewFull"
          ></button>

          <button @click="toggleGroupMode" class="ms-2 btn btn-primary fas fa-check-square" :title="t('group')"></button>
        </div>
      </div>
    </template>

    <div v-if="groupMode" class="d-flex align-items-center gap-2 border rounded p-2 mt-2">
      <label class="mb-0"><input type="checkbox" :checked="allSelected" @change="toggleAll" /> {{ t('select_all') }}</label>

      <div class="flex-grow-1"><small>{{ selected.length }}</small></div>

      <button class="btn btn-success btn-sm" :disabled="selected.length === 0" @click="moveFiles.open([...selected])">{{ t('move') }}</button>
      <button class="btn btn-danger btn-sm" :disabled="selected.length === 0" @click="deleteSelected">{{ t('delete') }}</button>
      <button class="btn btn-secondary btn-sm" @click="toggleGroupMode">{{ t('cancel') }}</button>
    </div>

    <div v-if="ready" class="mt-2">
      <!-- Empty -->
      <div v-if="directory.length === 0" class="my-5 py-5 text-center">...</div>

      <!-- FOlders -->
      <div v-for="(el, index) in directory.filter((e) => e.type === 'dir')">
        <div class="d-flex folder">
          <div class="me-2" v-if="groupMode">
            <input type="checkbox" :checked="selected.includes(el.name)" @click.stop="toggleSelected(el.name)" />
          </div>

          <div class="me-2">
            <i class="far fa-folder pointer" @click="groupMode ? toggleSelected(el.name) : changeFolder(el.name)"></i>
          </div>
          <div class="pointer" @contextmenu.prevent="onRightClick($event, el, index)" @click="groupMode ? toggleSelected(el.name) : changeFolder(el.name)">
            {{ el.name }}
          </div>
        </div>
      </div>
      <!-- files -->
      <div :class="{ 'd-flex flex-wrap': viewFull }" class="">
        <FileItem
          @contextmenu.prevent="onRightClick($event, el, index)"
          @click="openFile(el)"
          v-for="el in directory.filter((e) => e.type !== 'dir')"
          :key="el.name"
          :el="el"
          :viewFull="viewFull"
          :viewSize="viewSize"
          :path="path"
          :groupMode="groupMode"
          :checked="selected.includes(el.name)"
          @toggle="toggleSelected(el.name)"
        />
      </div>
    </div>
    <div v-else class="text-center p-3">
      <div class="spinner-border text-info"></div>
    </div>
  </component>

  <!-- ModalWindow create file-->
  <Dialog v-model:visible="modalAddNewFile.visible" :header="t('create_file')" modal style="width: 600px">
    <div class="mb-3">
      <input v-model="modalAddNewFile.fileName" type="text" class="form-control" :placeholder="t('name_file')" @keyup.enter="createFolder" />
    </div>
    <template #footer>
      <!-- <button class="btn btn-secondary btn-sm" @click="modalAddNewFolder.visible = false">{{ t('cancel') }}</button> -->
      <button class="btn btn-success btn-sm" @click="createFile">{{ t('create') }}</button>
    </template>
  </Dialog>

  <!-- ModalWindow create folder-->
  <Dialog v-model:visible="modalAddNewFolder.visible" :header="t('create_folder')" modal style="width: 600px">
    <div class="mb-3">
      <input v-model="modalAddNewFolder.newFolderName" type="text" class="form-control" :placeholder="t('name_folder')" @keyup.enter="createFolder" />
    </div>
    <template #footer>
      <!-- <button class="btn btn-secondary btn-sm" @click="modalAddNewFolder.visible = false">{{ t('cancel') }}</button> -->
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
  <UploadImage ref="uploadImageRef" :path="path" :directory="directory" @uploaded="onFileUploaded" />

  <MediaView v-model:visible="mediaFile.visible" :el="mediaFile.el" :kind="mediaFile.kind" :path="path" />

  <MoveFiles
    v-model:visible="moveFiles.visible"
    :names="moveFiles.names"
    :fromPath="path"
    :startDirectory="startDirectory"
    :directory="directory"
    @moved="onMoved"
  />

  <ContextMenu ref="menu" :model="items" />

  <EditFile :fileName="editFile.fileName" :zIndex="1000" v-if="editFile.visible" @close="editFile.visible = false"></EditFile>
</template>
<style>
.p-contextmenu-item-content a {
  text-decoration: none !important;
}

.path-element {
  cursor: pointer;
  /* text-decoration: underline; */
  box-shadow: inset 0 -1px 0 #bbb;
}

.path-element:hover {
  color: #198754;
  box-shadow: inset 0 -1px 0 #198754;
}
[data-bs-theme='dark'] .path-element:hover {
  color: #9dff00; /* светлее зелёный */
}

.path-end {
  font-weight: bold;
}

.folder {
  color: var(--bs-indigo);
}

[data-bs-theme='dark'] .folder {
  color: #a3f0ff; /* светлее */
}
</style>
