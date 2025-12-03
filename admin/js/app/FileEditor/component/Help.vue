<script setup>
import { useArticleStore } from '../store';
const store = useArticleStore();
</script>

<template>
  <Dialog v-model:visible="store.statusHelp" header="Справка" modal class="w-50">
    <ul class="list-unstyled m-0">
      <li><kbd>Ctrl+S</kbd> — сохранить</li>
      <li><kbd>Shift_</kbd> — снипеты блейда</li>
      <li><kbd>Alt+1</kbd> — перейти в статье по выбранному слову</li>

      <li><kbd>Alt+3</kbd> — панель дерева</li>
      <li><kbd>Alt+4</kbd> — боковое меню</li>
      <li><kbd>Alt+5</kbd> — панель контроллера</li>
      <li><kbd>Alt+6</kbd> — скрыть/отобразить панель блейда</li>
    </ul>
  </Dialog>
</template>
