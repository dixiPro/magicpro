<script setup>
import { onUnmounted } from "vue";
import { patchBladeTokenizer, getBladeTheme } from "./bladeLanguagePatch.js"; // Проверь путь

const body = defineModel("value", { default: "" });

const props = defineProps({
  language: { type: String, default: "html" },
  theme: { type: String, default: "vs-blade" },
});

const emit = defineEmits(["editor"]);

let editorMonaco = null;

const onBeforeMount = async (monaco) => {
  // Патчим токенизатор и определяем тему
  await patchBladeTokenizer(monaco);
  monaco.editor.defineTheme("vs-blade", getBladeTheme("vs"));
  // Принудительно применяем тему
  monaco.editor.setTheme("vs-blade");
};

const onReady = (editor, monaco) => {
  window.emmetMonaco?.emmetHTML(monaco, ["html", "php", "javascript", "twig", "json"]);

  editorMonaco = editor;

  // Устанавливаем язык и тему после создания модели
  const model = editor.getModel();
  monaco.editor.setModelLanguage(model, props.language);
  monaco.editor.setTheme("vs-blade"); // Повторно применяем тему

  window.addEventListener("keydown", handleKeydown);
  editor.focus();
};

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeydown);
});

const handleKeydown = (event) => {
  if (event.altKey && event.code === "Digit0") {
    if (!editorMonaco?.hasTextFocus()) return;
    event.preventDefault();
    const selection = editorMonaco.getModel().getValueInRange(
      editorMonaco.getSelection()
    );
    emit("editor", { command: "articleByName", value: selection });
  }
};
</script>

<template>
  <div style="height: 100%">
    <vue-monaco-editor v-model:value="body" :language="language" :theme="theme" :options="{ automaticLayout: true }"
      style="height: 100%" @beforeMount="onBeforeMount" @mount="onReady" />
  </div>
</template>