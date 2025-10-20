<script setup>
import {
    ref,
    computed,
    onMounted,
    onUnmounted,
    watch,
    reactive,
    nextTick,
} from "vue";

import AceEditor from "./AceEditor.vue";
import FileManager from "./FileManager.vue";
import TreeArticle from "./TreeArticle.vue";
import { formatBlade } from './formatBlade.js';
import { formatPhp } from './formatPhp.js';

const articleId = defineModel("articleId", 1);
const article = ref({ routeParams: {} });
const routeParamsString = ref("{}");

const aceTheme = ref("chrome"); // "monokai";

const aceThemes = [
    "chrome",
    "github",
    "xcode",
    "solarized_light",
    "textmate",
    "tomorrow",
    "kuroir",
    "eclipse",
    "monokai",
    "dracula",
    "twilight",
    "solarized_dark",
    "merbivore_soft",
];

const ready = ref({
    show: false,
    x: 0,
    y: 0,
});
onMounted(() => {
    window.addEventListener("keydown", handleKeydown);

    // Фикс размеры для слоя
    const rect = document
        .getElementById("editor-layer")
        .getBoundingClientRect();
    ready.value.y = window.innerHeight - rect.top; // расстояние до нижнего края экрана
    ready.value.x = window.innerWidth; // Ширина окна
    ready.value.show = true;
    // setTimeout(() => {
    //     ready.value.show = true;
    // }, 500);

    loadRec(articleId.value);

    watch(articleId, () => {
        loadRec(articleId.value);
    });

    watch(
        () => article.value,
        () => {
            treeRef.value?.changeNode(article.value);
        },
        { deep: true }
    );
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeydown);
});

async function loadRec(id) {
    article.value = await apiArt({
        command: "getById",
        id: id,
    });
    history.pushState(id, null, "#" + id);
}

async function saveRec() {
    const jsStr = JSON.parse(
        routeParamsString.value ? routeParamsString.value : "{}"
    );
    article.value.routeParams = jsStr;
    article.value = await apiArt({
        command: "saveById",
        article: article.value,
    });
    document.showToast("Сохранено");
    routeParamsString.value = JSON.stringify(
        article.value.routeParams,
        null,
        2
    );
}

// дать контроллер по умолчанию
const getController = async () => {
    try {
        const $arr = await apiArt({
            command: "getDefaultController",
            id: 1, // не важно какое имя
        });
        article.value.controller = $arr.controller;
    } catch (error) {
        console.log(error);
    }
};

const getLiveWareController = async () => {
    try {
        const $arr = await apiArt({
            command: "getDefaultLiveWareController",
            id: 1, // не важно какое имя
        });
        article.value.controller = $arr.controller;
    } catch (error) {
        console.log(error);
    }
};

// горчие кравиши
const handleKeydown = (event) => {
    if (event.ctrlKey && event.code === "KeyS") {
        event.preventDefault(); // Prevent browser's default save action
        saveRec();
        return;
    }
    // левое меню
    if (event.altKey && event.code === "Digit4") {
        addPannel.value = !addPannel.value;
        event.preventDefault(); // Prevent browser's default save action
        return;
    }
    // блейд 99%
    if (event.altKey && event.code === "Digit1") {
        splitStatusEditorObj.value.set("hideController");
        event.preventDefault(); // Prevent browser's default save action
        return;
    }
    // контроллер 99%
    if (event.altKey && event.code === "Digit2") {
        splitStatusEditorObj.value.set("hideBlade");
        event.preventDefault(); // Prevent browser's default save action
        return;
    }
    // дерево 99%
    if (event.altKey && event.code === "Digit3") {
        const status =
            splitTreeStatusObj.value.status == "hideTree"
                ? "normal"
                : "hideTree";
        splitTreeStatusObj.value.set(status);
        event.preventDefault(); // Prevent browser's default save action
        return;
    }
};

// переход на статью из формы
const gotoArticle = ref("");
const gotoArticleByName = async (name) => {
    try {
        const result = await apiArt({
            command: "articleByName",
            name: name,
        });
        console.log(result);
        window.open(location.pathname + "#" + result.id, "_blank");
    } catch (error) { }
};

// Вызов из редактора загрузить статью в выделенной области
async function handleEditorEvent(obj) {
    if (obj.command == "articleByName") {
        gotoArticleByName(obj.value);
    }
}

// сплитер редактор
const splitEditorRef = ref();
const splitEditorKey = ref(0); // для обновления

// сплитер состояние контроллер блейд
const splitStatusEditorObj = ref({
    status: "normal",
    data: {
        normal: { l: 50, r: 50 },
        hideController: { l: 1, r: 99 },
        hideBlade: { l: 99, r: 1 },
    },
    set: function (newStatus) {
        if (newStatus == this.status && this.status != "normal") {
            newStatus = "normal";
        }
        this.status = newStatus;
        splitEditorKey.value++;
    },
    getLeft: function () {
        return this.data[this.status].l;
    },
    getRight: function () {
        return this.data[this.status].r;
    },
});
// при ресайзе сплиттера
const splitterEditorOnResizeEnd = (e) => {
    splitStatusEditorObj.value.status = "normal";
    splitStatusEditorObj.value.data.normal.l = e.sizes[0];
    splitStatusEditorObj.value.data.normal.r = e.sizes[1];
};

// сплитер дерева
const splitTreeRef = ref();
const splitTreeKey = ref(0); // для обновления

// сплитер состояние дерева
const splitTreeStatusObj = ref({
    status: "normal",
    data: {
        normal: { l: 15, r: 85 },
        hideTree: { l: 1, r: 99 },
    },
    set: function (newStatus) {
        if (newStatus == this.status && this.status != "normal") {
            newStatus = "normal";
        }
        this.status = newStatus;
        splitTreeKey.value++;
    },
    getLeft: function () {
        return this.data[this.status].l;
    },
    getRight: function () {
        return this.data[this.status].r;
    },
});
// при ресайзе сплиттера
const splitterTreeOnResizeEnd = (e) => {
    splitTreeStatusObj.value.status = "normal";
    splitTreeStatusObj.value.data.normal.l = e.sizes[0];
    splitTreeStatusObj.value.data.normal.r = e.sizes[1];
};

// статус хелпа
const helpDialogShow = ref(false);
// статус правой панели
const addPannel = ref(false);

// Дерево
const treeRef = ref(null);

const treeEmmit = (obj) => {
    if (obj.command == "changeActiveArt") {
        articleId.value = obj.value;
    }
};

const url = computed(() => {
    return article.value.name === "index" ? "/" : "/" + article.value.name;
});

const folderOpen = ref(false);

const translitOn = computed(() => {
    if (article.value.name === "") {
        return;
    }
    return /^[a-z0-9_-]+$/i.test(article.value.name);
});

function translit() {
    article.value.name = translitString(article.value.name.trim());
}

async function formatDocument() {
    try {
        const result = await formatBlade(article.value.body, 4);
        article.value.body = result;

        const result1 = await formatPhp(article.value.controller, 4);
        article.value.controller = result1;
        document.showToast("Отформатировано");

    } catch (error) {
        document.showToast("Ошибка форматирования: " + error.message, "error");
    }

}

</script>

<template>
    <div>
        <FileManager v-model="folderOpen" />

        <div class="row gx-2 py-2 mx-0" style="background: #ffff001f">
            <div class="col-auto">
                <a v-if="article.isRoute" target="_blank" class="fas fa-external-link-alt btn btn-sm btn-primary"
                    :href="url"></a>
                <span v-else class="fas fa-external-link-alt btn btn-sm btn-secondary"></span>
            </div>
            <div class="col-3">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm" v-model="article.name" />
                    <button v-if="!translitOn" class="btn btn-sm btn-primary" @click="translit">
                        Translit→
                    </button>
                </div>
            </div>

            <div class="col-3" v-if="translitOn">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm" v-model="article.title" />
                    <button class="btn btn-sm btn-primary" @click="saveRec">
                        Save→
                    </button>
                </div>
            </div>

            <div class="col-1">
                <div class="input-group input-group-sm">
                    <input placeholder="перейти" type="text" class="form-control" v-model="gotoArticle" />
                    <button class="btn btn-primary fas fa-angle-right" @click="gotoArticleByName(gotoArticle)"></button>
                </div>
            </div>
            <div class="col-auto">
                <span v-if="splitTreeStatusObj.status == 'hideTree'" class="fas fa-sitemap btn btn-success"
                    @click="splitTreeStatusObj.set('normal')"></span>

                <span v-if="splitTreeStatusObj.status == 'normal'" class="fas fa-ellipsis-h btn btn-success"
                    @click="splitTreeStatusObj.set('hideTree')"></span>
            </div>
            <div class="col-auto">
                <span v-if="splitStatusEditorObj.status == 'normal'" class="fas fa-angle-left btn btn-success"
                    @click="splitStatusEditorObj.set('hideController')"></span>

                <span v-if="splitStatusEditorObj.status == 'hideBlade'" @click="splitStatusEditorObj.set('normal')"
                    class="fa fa-columns btn btn-success"></span>

                <span v-if="splitStatusEditorObj.status == 'hideController'"
                    @click="splitStatusEditorObj.set('hideBlade')" class="fas fa-angle-right btn btn-success"></span>
            </div>
            <div class="col-auto align-self-center">
                <button class="btn fas fa-magic btn-success" @click="formatDocument">1</button>
            </div>
            <div class="col-auto">
                <button @click="helpDialogShow = !helpDialogShow" class="fas fa-question btn btn-success"></button>
            </div>
            <div class="col-auto">
                <select v-model="aceTheme" class="form-select form-select-sm">
                    <option v-for="theme in aceThemes" :key="theme" :value="theme">
                        {{ theme }}
                    </option>
                </select>
            </div>

            <div class="col text-end">
                <button class="btn  btn-success fas fa-folder-open" @click="folderOpen = !folderOpen"></button>
                <button class="ms-1 btn btn-success fas fa-bars" @click="addPannel = !addPannel"></button>
            </div>
        </div>

        <div :style="{ height: ready.y + 'px', width: ready.x + 'px' }" id="editor-layer">
            <div v-if="ready.show">
                <Splitter :style="{ height: ready.y + 'px', width: ready.x + 'px' }"
                    @resizeend="splitterTreeOnResizeEnd" ref="splitTreeRef" :key="splitTreeKey">
                    <SplitterPanel :size="splitTreeStatusObj.getLeft()" :minSize="0">
                        <TreeArticle :idArticle="articleId" @tree="treeEmmit" ref="treeRef"></TreeArticle>
                    </SplitterPanel>

                    <SplitterPanel :size="splitTreeStatusObj.getRight()" :minSize="1">
                        <Splitter style="height: 100%" ref="splitEditorRef" @resizeend="splitterEditorOnResizeEnd"
                            :key="splitEditorKey">
                            <SplitterPanel :size="splitStatusEditorObj.getLeft()" :minSize="1">

                                <AceEditor v-model="article.controller" lang="php" :theme="aceTheme" :height="ready.y"
                                    @editor="handleEditorEvent" />
                            </SplitterPanel>
                            <SplitterPanel :size="splitStatusEditorObj.getRight()" :minSize="1">
                                <AceEditor v-model="article.body" lang="html" :theme="aceTheme" :height="ready.y"
                                    @editor="handleEditorEvent" />
                            </SplitterPanel>
                        </Splitter>
                    </SplitterPanel>
                </Splitter>
            </div>
        </div>
    </div>

    <Dialog v-model:visible="helpDialogShow" header="Справка" modal class="w-50">
        <ul class="list-unstyled m-0">
            <li><kbd>Ctrl+S</kbd> — сохранить</li>
            <li><kbd>Alt+0</kbd> — перейти в статье по выбранному слову</li>
            <li><kbd>Alt+1</kbd> — скрыть/отобразить панель блейда</li>
            <li><kbd>Alt+2</kbd> — скрыть/отобразить панель контроллера</li>
            <li><kbd>Alt+3</kbd> — скрыть/отобразить панель дерева</li>
        </ul>
    </Dialog>
    <Drawer v-model:visible="addPannel" header="Параметры" position="right">
        <div class="">
            <div class="my-2">
                <input type="checkbox" class="form-check-input" v-model="article.isRoute" />
                route
            </div>

            <div class="my-2">
                <input type="checkbox" class="form-check-input" v-model="article.menuOn" />
                menu
            </div>

            <div class="my-2">
                <button class="btn btn-success btn-sm" @click="getController()">
                    Обычный контроллер
                </button>
            </div>
            <div class="my-2">
                <button class="btn btn-success btn-sm" @click="getLiveWareController()">
                    LiveWare контроллер
                </button>
            </div>
        </div>

        <pre>
                    {{ article }}          
        </pre>
    </Drawer>
</template>
<style>
:root {
    --p-dialog-header-padding: 0.2rem 1rem;
    /* новое значение */
}

.p-dialog ul li {
    margin-bottom: 0.5rem;
    /* или 0.75rem */
}
</style>
