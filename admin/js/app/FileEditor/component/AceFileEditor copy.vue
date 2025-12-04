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

import 'ace-builds/src-noconflict/mode-css';
import 'ace-builds/src-noconflict/snippets/css';
import 'ace-builds/src-noconflict/mode-javascript';
import 'ace-builds/src-noconflict/snippets/javascript';

ace.config.set('basePath', '/vendor/magicpro/ace');

const id = ref(useId());

const props = defineProps({
  fileExtention: { type: String, default: 'html' },
  theme: { type: String, default: 'monokai' },
  readOnly: { type: Boolean, default: false },
  tabSize: { type: Number, default: 4 },
  wrap: { type: Boolean, default: false },
  height: { type: Number, default: 100 },
});

const body = defineModel({ type: String, default: '' });
const el = ref(null);
let editor = null;

function getMode() {
  const modeExt = {
    js: 'ace/mode/javascript',
    css: 'ace/mode/css',
  };
  return modeExt[props.fileExtention] ? modeExt[props.fileExtention] : 'ace/mode/text';
}

const getTheme = (theme) => `ace/theme/${theme}`;

onMounted(() => {
  const mode = getMode();
  editor = ace.edit(id.value, {
    value: body.value,
    mode: getMode(),
    theme: getTheme(props.theme),
    readOnly: props.readOnly,
    wrap: props.wrap,
    tabSize: props.tabSize,
    useWorker: false,
  });

  editor.setOptions({
    enableBasicAutocompletion: true,
    enableLiveAutocompletion: true,
    // enableSnippets: true,
    enableEmmet: true,
  });

  editor.setFontSize(14);

  editor.session.on('change', () => {
    body.value = editor.getValue();
  });
});

watch(body, (val) => {
  if (!editor) return;
  const current = editor.getValue();
  if (val !== current) editor.setValue(val);
});

onBeforeUnmount(() => editor?.destroy());

watch(
  () => props.fileExtention,
  (v) => {
    const mode = getMode();
    editor?.session.setMode(mode);
  }
);
watch(
  () => props.theme,
  (v) => editor?.setTheme(getTheme(v))
);
</script>
<template>
  <div ref="el" :id="id" style="width: 100%; height: 100%"></div>
</template>
