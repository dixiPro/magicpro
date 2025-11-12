<script setup>
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick, toRaw } from 'vue';

import AceEditor from './AceEditor.vue';
import TreeArticle from './TreeArticle.vue';

import { useArticleStore } from '../store';
const store = useArticleStore();

import FindText from './FindText.vue';

const ready = ref({
  show: false,
  x: 0,
  y: 0,
});

onMounted(() => {
  // store.treeSplitterRef = splitterRef;
  // Фикс размеры для слоя
  const rect = document.getElementById('editor-layer').getBoundingClientRect();
  ready.value.y = window.innerHeight - rect.top; // расстояние до нижнего края экрана
  ready.value.x = window.innerWidth; // Ширина окна
  ready.value.show = true;

  store.toggleTreeSplitter = toggleTreeSplitter;
  store.toggleEditorSplitter = toggleEditorSplitter;
});

// сплитер ДЕРЕВА
const splitterTreeRef = ref(null);
const leftTreeSplitter = ref(20);
let savedTreeSplitter = 0;
const modeTreeSplitter = ref('open');

// при ресайзе сплиттера
const splitterTreeOnResizeEnd = (e) => {
  leftTreeSplitter.value = e.sizes[0];
};

function toggleTreeSplitter() {
  if (modeTreeSplitter.value == 'open') {
    savedTreeSplitter = leftTreeSplitter.value;
    leftTreeSplitter.value = 0;
    modeTreeSplitter.value = 'hidden';
  } else {
    leftTreeSplitter.value = savedTreeSplitter;
    modeTreeSplitter.value = 'open';
  }
  store.modeTreeSplitter = modeTreeSplitter.value;
  splitterTreeRef.value.resetState();
}

// Сплиттер Редактор
const splitEditorRef = ref();
const leftSplitEditor = ref(50);
let savedEditorSplitter = 0;
const modeEditorSplitter = ref('full');

//
watch(
  () => store?.article.routeParams.useController,
  () => {
    if (!store.article.routeParams.useController) {
      leftSplitEditor.value = 0;
      modeEditorSplitter.value = 'view';
    }

    if (store.article.routeParams.useController) {
      leftSplitEditor.value = 50;
      modeEditorSplitter.value = 'full';
    }

    store.modeEditorSplitter = modeEditorSplitter.value;
    splitEditorRef.value.resetState();
  }
);

function splitterEditorOnResizeEnd(e) {
  leftSplitEditor.value = e.sizes[0];
}

function toggleEditorSplitter() {
  switch (modeEditorSplitter.value) {
    case 'full':
      savedEditorSplitter = leftSplitEditor.value;
      leftSplitEditor.value = 0;
      modeEditorSplitter.value = 'view';
      break;

    case 'view':
      leftSplitEditor.value = 100;
      modeEditorSplitter.value = 'controller';
      break;

    case 'controller':
      leftSplitEditor.value = savedEditorSplitter;
      modeEditorSplitter.value = 'full';
      break;

    default:
      break;
  }

  store.modeEditorSplitter = modeEditorSplitter.value;
  splitEditorRef.value.resetState();
}

//
</script>

<template>
  <div>
    <div :style="{ height: ready.y + 'px', width: ready.x + 'px' }" id="editor-layer">
      <div v-if="ready.show">
        <Splitter :style="{ height: ready.y + 'px', width: ready.x + 'px' }" @resizeend="splitterTreeOnResizeEnd" ref="splitterTreeRef">
          <SplitterPanel :size="leftTreeSplitter" :minSize="0">
            <div style="position: relative">
              <button
                v-show="store.statusLeftPannel === 'tree'"
                style="position: absolute; right: 10px; z-index: 1000"
                class="btn btn-primary fas fa-search"
                @click="store.statusLeftPannel = 'find'"
              ></button>
            </div>

            <TreeArticle v-show="store.statusLeftPannel === 'tree'"></TreeArticle>
            <FindText v-show="store.statusLeftPannel === 'find'"></FindText>
          </SplitterPanel>

          <SplitterPanel :size="100 - leftTreeSplitter" :minSize="1">
            <Splitter style="height: 100%" @resizeend="splitterEditorOnResizeEnd" ref="splitEditorRef">
              <SplitterPanel :size="leftSplitEditor" :minSize="1">
                <AceEditor v-model="store.article.controller" lang="php" :theme="store.aceTheme" :height="ready.y" />
              </SplitterPanel>
              <SplitterPanel :size="100 - leftSplitEditor" :minSize="1">
                <AceEditor v-model="store.article.body" lang="html" :theme="store.aceTheme" :height="ready.y" />
              </SplitterPanel>
            </Splitter>
          </SplitterPanel>
        </Splitter>
      </div>
    </div>
  </div>
</template>
<style scoped></style>
