<script setup>
import { ref } from 'vue';
import Dialog from 'primevue/dialog';

// Подсказка: вопрос рядом с полем, по клику модальное окно с текстом.
// Иконка вшита в компонент, снаружи передаётся только текст и заголовок.
defineProps({
  text: { type: String, default: '' },
  header: { type: String, default: '' },
});

const visible = ref(false);
</script>

<template>
  <button
    type="button"
    class="btn btn-sm btn-outline-secondary rounded-circle py-0 px-2 ms-1"
    @click.stop="visible = true"
  >
    ?
  </button>

  <Dialog v-model:visible="visible" modal :draggable="false" :header="header" :style="{ width: '34rem' }">
    <div style="white-space: pre-line">{{ text }}</div>
  </Dialog>
</template>

<style scoped></style>
