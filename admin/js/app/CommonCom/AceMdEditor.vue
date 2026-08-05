<script setup>
import { ref, onMounted, onBeforeUnmount, watch, useId } from 'vue';

import ace from 'ace-builds/src-noconflict/ace';

import 'ace-builds/src-noconflict/mode-markdown';
import 'ace-builds/src-noconflict/theme-chrome';
import 'ace-builds/src-noconflict/theme-monokai';
import 'ace-builds/src-noconflict/ext-searchbox';

ace.config.set('basePath', '/vendor/dixipro/magicpro/ace');

const id = ref(useId());

const props = defineProps({
  theme: { type: String, default: 'chrome' },
  readOnly: { type: Boolean, default: false },
  tabSize: { type: Number, default: 4 },
  wrap: { type: Boolean, default: true },
});

const body = defineModel({ type: String, default: '' });

let editor = null;

const getTheme = (theme) => `ace/theme/${theme}`;

onMounted(() => {
  editor = ace.edit(id.value, {
    value: body.value,
    theme: getTheme(props.theme),
    readOnly: props.readOnly,
    wrap: props.wrap,
    tabSize: props.tabSize,
    useWorker: false,
  });

  editor.session.setMode('ace/mode/markdown');

  editor.setFontSize(14);

  editor.session.on('change', () => {
    body.value = editor.getValue();
  });
});

watch(body, (val) => {
  if (!editor) return;

  if (val !== editor.getValue()) editor.setValue(val);
});

watch(
  () => props.theme,
  (v) => editor?.setTheme(getTheme(v)),
);
watch(
  () => props.readOnly,
  (v) => editor?.setReadOnly(v),
);
watch(
  () => props.wrap,
  (v) => editor?.session.setUseWrapMode(v),
);

onBeforeUnmount(() => editor?.destroy());
</script>

<template>
  <div :id="id" style="width: 100%; height: 100%"></div>
</template>
