<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId, toRaw, unref } from 'vue';

const props = defineProps({
  action: Function, // async функция
});

const loading = ref(false);

async function handleClick() {
  loading.value = true;
  try {
    await props.action();
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <button :disabled="loading" @click="handleClick" class="btn btn-primary">
    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
    <slot></slot>
  </button>
</template>
