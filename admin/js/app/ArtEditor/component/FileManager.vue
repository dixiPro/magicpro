<script setup>
//
// uploadFile если файл уже существует?
//
import {
    ref,
    computed,
    onMounted,
    onUnmounted,
    watch,
    reactive,
    nextTick,
} from "vue";

import UploadFile from "./UploadFile.vue";
const uploadRef = ref(null);

const startDirectory = ref("/design/");
const path = ref("/design/");
path.value = startDirectory.value;
const directory = ref({});
const backToRoot = ref([]);

const viewFull = ref(true);
const viewSize = ref(160);

const folderOpen = defineModel({ type: Boolean, default: false });

const ready = ref(false);
const showNewFolderModal = ref(false);
const newFolderName = ref("");

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
    newFolderName.value = "";
    showNewFolderModal.value = true;
}

async function start() {
    try {
        ready.value = false;
        const res = await apiFile({ command: "start" });
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
            command: "mkdir",
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
        path.value = path.value + newPath + "/";
        await openFolder(path.value);
    } catch (e) {
        console.error(e);
    } finally {
        ready.value = true;
    }
}

function onFileUploaded(fileInfo) {
    document.showToast("Загружен " + fileInfo.name);
    // удаляем файл если был такой
    directory.value = directory.value.filter(
        (item) => item.name !== fileInfo.name
    );
    directory.value.unshift(fileInfo);
    // fileI.nfo = { uploaded: true, name, path, mime, size }
    // тут можно обновить список файлов
}

async function openFolder(pathVal) {
    try {
        ready.value = false;
        directory.value = await apiFile({
            command: "dirList",
            path: pathVal,
        });
    } catch (error) {
        console.error(error);
    } finally {
        ready.value = true;
    }
}

async function deleteFileOrFolder(name) {
    if (!(await document.confirmDialog("Удалить?" + path.value + name))) return;
    try {
        ready.value = false;

        await apiFile({
            command: "delete",
            path: path.value,
            name: name,
        });
        directory.value = directory.value.filter((item) => item.name !== name);
        document.showToast("Удален " + name);
    } catch (e) {
        console.error(e);
    } finally {
        ready.value = true;
    }
}

function copyClipBoard(fullLink) {
    const textarea = document.createElement("textarea");
    textarea.value = fullLink;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";
    document.body.appendChild(textarea);
    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length); // для мобильных
    try {
        const ok = document.execCommand("copy");
        document.showToast(
            ok ? "Скопирована ссылка " + fullLink : "Ошибка копирования ссылки",
            ok ? "success" : "error"
        );
    } catch (e) {
        document.showToast("Ошибка копирования ссылки", "error");
    } finally {
        document.body.removeChild(textarea);
    }
}

function copyImg(el) {
    const s = `<img src="${encodeURI(path.value + el.name)}" alt="${
        el.name
    }" height="${el.height}" width="${el.width}" />`;
    copyClipBoard(s);
}

function copyLink(el) {
    copyClipBoard(encodeURI(path.value + el.name));
}

const menu = ref();
const items = ref([]);

const stopEscPropagation = (e) => {
    if (e.key === "Escape") {
        e.stopPropagation();
        if (menu.value?.hide) {
            menu.value.hide();
        }

        document.removeEventListener("keydown", stopEscPropagation, true);
    }
};

function onRightClick(event, el) {
    // menu.value.hide();
    document.addEventListener("keydown", stopEscPropagation, true);
    items.value = [
        {
            label: "Удалить",
            icon: "fas fa-trash",
            command: () => {
                deleteFileOrFolder(el.name);
            },
        },

        {
            label: "Переименовать",
            icon: "fas fa-plus",
            command: () => {},
        },
    ];

    if (el.type === "file") {
        items.value.push({
            label: "Копировать путь",
            icon: "fas fa-link",
            command: () => {
                copyLink(el);
            },
        });
    }

    if (el.type === "file" && el.isImage) {
        items.value.push({
            label: "Копировать Img",
            icon: "far fa-file-image",
            command: () => {
                copyImg(el);
            },
        });
    }

    // показ меню
    menu.value.show(event);
}

function calcImgStyle(x, y) {
    if (x >= y) {
        return "width :" + (viewSize.value - 2) + "px;";
    } else {
        return "height :" + (viewSize.value - 2) + "px;";
    }
}

function backPathEl(back, index) {
    const arr = path.value.split("/");
    console.log(arr, index);
    const a = arr[index + 2];
    return a ? a + "/" : "";
}

async function navStart() {
    path.value = startDirectory.value;
    backToRoot.value = [];
    await openFolder(path.value);
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
    const arr1 = path.value.split("/").filter((el) => el !== "");
    const arr = arr1.map((el, index) => {
        return {
            name: el,
            index: index,
        };
    });
    return arr;
});
</script>

<template>
    <Dialog v-model:visible="folderOpen" modal position="top" class="w-50 mt-4">
        <template #header>
            <div
                class="d-flex align-items-center gap-2 p-1 rounded w-100 border"
            >
                <button
                    v-if="path !== startDirectory"
                    @click="goBack"
                    class="btn btn-success fas fa-chevron-left"
                ></button>
                <button
                    @click="openNewFolderModal"
                    class="btn btn-success fas fa-folder-plus"
                ></button>
                <button
                    @click="uploadFile"
                    class="btn btn-success fas fa-file-upload"
                ></button>

                <div class="flex-grow-1">
                    <span v-for="(el, i) in backPathArr" :key="i">
                        <span
                            class="path-element"
                            :class="{
                                'path-end': i === backPathArr.length - 1,
                            }"
                            @click="navBackTo(i)"
                            >{{ el.name }}</span
                        ><span class="mx-1" style="font-size: 80%"
                            >/</span
                        ></span
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
            <div v-if="directory.length === 0" class="my-5 py-5 text-center">
                ...
            </div>

            <!-- Папки -->
            <div
                v-for="(el, index) in directory.filter((e) => e.type === 'dir')"
            >
                <div class="d-flex folder">
                    <div class="me-2">
                        <i
                            class="far fa-folder pointer"
                            @click="changeFolder(el.name)"
                        ></i>
                    </div>
                    <div
                        class="pointer"
                        @contextmenu.prevent="onRightClick($event, el)"
                        @click="changeFolder(el.name)"
                    >
                        {{ el.name }}
                    </div>
                </div>
            </div>
            <!-- файлы -->
            <div :class="{ 'd-flex flex-wrap': viewFull }" class="">
                <template
                    v-for="(el, index) in directory.filter(
                        (e) => e.type !== 'dir'
                    )"
                >
                    <div
                        v-if="!viewFull"
                        class="d-flex my-1"
                        @contextmenu.prevent="onRightClick($event, el)"
                    >
                        <div class="me-2 icon">
                            <i
                                :class="{
                                    'far fa-file-image': el.isImage,
                                    'far fa-file': !el.isImage,
                                }"
                            ></i>
                        </div>
                        <div class="">
                            {{ el.name }}
                            <span v-if="el.isImage"
                                ><span class="ms-1" style="font-size: 85%"
                                    >({{ el.width }}x{{ el.height }})</span
                                ></span
                            >
                        </div>
                    </div>
                    <div
                        v-else
                        class="thumb-pic"
                        :style="
                            'width: ' +
                            viewSize +
                            'px; height: ' +
                            viewSize +
                            'px;'
                        "
                        @contextmenu.prevent="onRightClick($event, el)"
                    >
                        <template v-if="el.isImage">
                            <img
                                :src="path + el.name + '?' + el.date"
                                :style="calcImgStyle(el.width, el.height)"
                            />
                            <div class="file-name">
                                {{ el.name }}<br />({{ el.width }}x{{
                                    el.height
                                }})
                            </div>
                        </template>
                        <div v-else>
                            <i class="far fa-file"></i>
                            {{ el.name }}
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <div v-else class="text-center p-3">
            <div class="spinner-border text-info"></div>
        </div>
    </Dialog>

    <!-- Модалка создания папки -->
    <Dialog v-model:visible="showNewFolderModal" header="Создать папку" modal>
        <div class="mb-3">
            <label class="form-label">Имя новой папки</label>
            <input
                v-model="newFolderName"
                type="text"
                class="form-control"
                placeholder="Новая папка"
                @keyup.enter="createFolder"
            />
        </div>
        <template #footer>
            <button
                class="btn btn-secondary btn-sm"
                @click="showNewFolderModal = false"
            >
                Отмена
            </button>
            <button class="btn btn-success btn-sm" @click="createFolder">
                Создать
            </button>
        </template>
    </Dialog>

    <UploadFile
        ref="uploadRef"
        :path="path"
        :directory="directory"
        @uploaded="onFileUploaded"
        class="w-50"
    />

    <ContextMenu ref="menu" :model="items" />
</template>
<style>
.thumb-pic {
    margin: 10px;
    position: relative;
    background: #f0f0f0;
    border: 1px solid #ccc;
}

.thumb-pic .file-name {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    font-size: 12px;
    padding: 2px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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

.path-end {
    font-weight: bold;
}

.folder {
    color: var(--bs-indigo);
}
</style>
