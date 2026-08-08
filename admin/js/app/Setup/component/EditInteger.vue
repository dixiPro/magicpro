<script setup>
/**
 * Целое число с границами из схемы.
 *
 * v-model.number: без него значение уедет строкой, а на сервере тип проверяется.
 * Границы стоят и в input, и в проверке на сервере: браузер подсказывает, сервер
 * не верит на слово.
 */
const props = defineProps({
  min: { type: Number, default: null },
  max: { type: Number, default: null },
  mutable: { type: Boolean, required: true },
});

const value = defineModel();
</script>

<template>
  <input
    type="number"
    class="form-control form-control-sm"
    v-model.number="value"
    :min="min ?? undefined"
    :max="max ?? undefined"
    :disabled="!mutable"
  />
</template>
