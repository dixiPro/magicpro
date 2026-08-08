<script setup>
// Выбор из готового списка: значения приходят из схемы, своё вписать нельзя.
const props = defineProps({
  values: { type: Array, required: true },
  mutable: { type: Boolean, required: true },
});

const value = defineModel();
</script>

<template>
  <select class="form-select form-select-sm" v-model="value" :disabled="!mutable">
    <option v-for="item in values" :key="item" :value="item">{{ item }}</option>
  </select>
</template>
