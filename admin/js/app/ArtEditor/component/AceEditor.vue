<script setup>
import { ref, onMounted, onBeforeUnmount, watch, useId } from 'vue';
import ace from 'ace-builds/src-noconflict/ace';

import 'ace-builds/src-noconflict/mode-html';
import 'ace-builds/src-noconflict/mode-php';
import 'ace-builds/src-noconflict/ext-language_tools';
import 'ace-builds/src-noconflict/ext-emmet';
import 'ace-builds/src-noconflict/theme-monokai';
import 'ace-builds/src-noconflict/theme-github';
import 'ace-builds/src-noconflict/theme-chrome';
import 'ace-builds/src-noconflict/theme-xcode';
import 'ace-builds/src-noconflict/theme-solarized_light';
import 'ace-builds/src-noconflict/theme-dracula';
import 'ace-builds/src-noconflict/theme-twilight';
import 'ace-builds/src-noconflict/ext-searchbox';

import 'ace-builds/src-noconflict/snippets/html';
import 'ace-builds/src-noconflict/snippets/php';

import { enableBladeColoring } from './enableBladeColoring';
import { snippetsBlade } from './snippetsBlade';

import { useArticleStore } from '../store';
const store = useArticleStore();

const id = ref(useId());

const props = defineProps({
  lang: { type: String, default: 'html', validator: (v) => ['html', 'php', 'blade'].includes(v) },
  theme: { type: String, default: 'monokai' },
  readOnly: { type: Boolean, default: false },
  tabSize: { type: Number, default: 4 },
  wrap: { type: Boolean, default: false },
  height: { type: Number, default: 100 },
});

const body = defineModel({ type: String, default: 'XXXXXXXX' });
const el = ref(null);
let editor = null;

const getMode = () => (props.lang === 'php' ? 'ace/mode/php' : 'ace/mode/html');
const getTheme = (theme) => `ace/theme/${theme}`;

onMounted(() => {
  enableBladeColoring();

  editor = ace.edit(id.value, {
    value: body.value,
    mode: getMode(props.lang),
    theme: getTheme(props.theme),
    readOnly: props.readOnly,
    wrap: props.wrap,
    tabSize: props.tabSize,
    useWorker: false,
  });

  editor.renderer.setScrollMargin(0, 80, 0, 0);

  editor.setOptions({
    enableBasicAutocompletion: true,
    enableLiveAutocompletion: true,
    // enableSnippets: true,
    enableEmmet: true,
  });

  editor.setFontSize(14);

  // 🔹 Alt + 1 → отправка выделенного текста
  editor.commands.addCommand({
    name: 'sendSelectedToParent',
    bindKey: { win: 'Alt-1', mac: 'Alt-1' },
    exec(ed) {
      const selection = ed.session.getTextRange(ed.getSelectionRange());
      if (selection.trim() === '') {
        document.showToast('Статья не выделена');
        return;
      }
      store.gotoArticleByName(selection);
    },
  });

  if (props.lang !== 'php') {
    snippetsBlade(editor);
  }
  //

  editor.session.on('change', () => {
    body.value = editor.getValue();
  });

  // ждем search для php
  if (props.lang === 'php')
    watch(
      () => store.searchTextController,
      (val) => {
        if (!val.trim()) return;
        editor.find(val);
        editor.execCommand('find');
        store.searchTextController = '';
      },
    );

  // ждем search для html
  if (props.lang === 'html') {
    watch(
      () => store.searchTextView,
      (val) => {
        if (!val.trim()) return;
        editor.find(store.searchTextView);
        editor.execCommand('find');
        store.searchTextView = '';
      },
    );
  }
});

watch(body, (val) => {
  if (!editor) return;
  const current = editor.getValue();
  if (val !== current) editor.setValue(val);
});

onBeforeUnmount(() => editor?.destroy());

watch(
  () => props.lang,
  (v) => editor?.session.setMode(getMode(v)),
);
watch(
  () => props.theme,
  (v) => editor?.setTheme(getTheme(v)),
);
watch(
  () => props.readOnly,
  (v) => editor?.setReadOnly(v),
);
watch(
  () => props.tabSize,
  (v) => editor?.session.setTabSize(v),
);
watch(
  () => props.wrap,
  (v) => editor?.session.setUseWrapMode(v),
);
</script>

<template>
  <div :style="'height: ' + height + 'px; width: 100%; position: relative;'">
    <div ref="el" :id="id" style="width: 100%; height: 100%"></div>
  </div>
</template>
<style>
.ace_content {
  padding-bottom: 100px;
}
</style>
