<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId, toRaw, unref } from 'vue';

const props = defineProps({
  defaultValue: { type: Boolean, required: true },
  mutable: { type: Boolean, required: true },
});

const value = defineModel();
</script>

<template>
  <div>
    <ToggleSwitch v-model="value" :disabled="!mutable" />
  </div>
  <div v-if="mutable">
    <small>{{ defaultValue }}</small>
  </div>
</template>
