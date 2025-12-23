<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId, toRaw, unref } from 'vue';

const props = defineProps({
  defaultValue: { type: String, required: true },
  mutable: { type: Boolean, required: true },
});

const value = defineModel();
</script>

<template>
  <div>
    <input type="text" class="form-control form-sm" v-model="value" :disabled="!mutable" />
  </div>
</template>
