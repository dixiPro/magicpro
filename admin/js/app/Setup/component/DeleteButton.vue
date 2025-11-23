<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

onMounted(() => {
  window.addEventListener('keydown', onKey);
});

onUnmounted(() => {
  window.removeEventListener('keydown', onKey);
});

function onKey(e) {
  if (e.key === 'Escape') {
    confirm.value = false;
    dubleclick.value = false;
  }
}

const props = defineProps({
  action: Function, // async функция
});

const confirm = ref(false);
const dubleclick = ref(false);

async function handleClick() {
  if (confirm.value && dubleclick.value) {
    props.action();
    confirm.value = false;
    dubleclick.value = false;
    return;
  }
  confirm.value = true;

  setTimeout(() => {
    dubleclick.value = true;
  }, 500);
}
</script>

<template>
  <div style="display: inline-block">
    <button v-if="confirm" @click="confirm = !confirm" class="fas fa-times btn btn-sm"></button>
    <button @click="handleClick" class="fas fa-trash btn btn-sm"></button>
  </div>
</template>
