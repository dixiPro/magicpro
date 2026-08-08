<script setup>
/**
 * Группа параметров: контейнер с вложенными полями.
 *
 * Значение — массив своих полей, поэтому правим его по месту. Пустой объект
 * подставляем сами: параметр мог появиться в схеме позже файла настроек, и
 * тогда значения ещё нет.
 */
import EditString from './EditString.vue';
import EditBoolean from './EditBoolean.vue';
import EditList from './EditList.vue';
import EditInteger from './EditInteger.vue';

const props = defineProps({
  fields: { type: Object, required: true },
});

const value = defineModel({ default: () => ({}) });

if (!value.value || typeof value.value !== 'object') value.value = {};

for (const [code, field] of Object.entries(props.fields)) {
  if (value.value[code] === undefined) value.value[code] = field.default;
}
</script>

<template>
  <div v-for="(field, code) in fields" :key="code" class="mb-2">
    <div class="small">
      <strong>{{ code }}</strong>
      <span class="text-muted ms-1">{{ field.label }}</span>
    </div>

    <EditInteger v-if="field.type == 'integer'" v-model="value[code]" :min="field.min ?? null"
      :max="field.max ?? null" :mutable="field.mutable" />
    <EditList v-else-if="field.type == 'list'" v-model="value[code]" :values="field.values ?? []"
      :mutable="field.mutable" />
    <EditBoolean v-else-if="field.type == 'boolean'" v-model="value[code]" :defaultValue="field.default"
      :mutable="field.mutable" />
    <EditString v-else v-model="value[code]" :defaultValue="String(field.default ?? '')"
      :mutable="field.mutable" />
  </div>
</template>
