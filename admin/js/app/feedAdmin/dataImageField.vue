<script setup>
import { computed } from 'vue';
import { apiFeed } from './api.js';
import dataImageUpload from './dataImageUpload.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Поле изображения записи.
 *
 * Файл живёт не в форме, а в медиатеке, поэтому загрузка и удаление пишутся в
 * базу сразу, не дожидаясь «Сохранить». В значении поля лежат только
 * метаданные: url, size, mime, x, y и alt, который заполняет оператор.
 *
 * alt отдельного запроса не требует и уходит вместе с записью, как остальные
 * поля формы.
 *
 * Самой картинки здесь нет: миниатюра выводится в левой колонке формы, рядом с
 * подписью поля. Загрузка тоже своя, в dataImageUpload.
 */
const props = defineProps({
  itemId: { type: [String, Number], required: true },
  code: { type: String, required: true },
  minWidth: { type: Number, default: 0 },
  ratio: { type: String, default: '' },
});

const value = defineModel({ type: Object, default: null });

const alt = computed({
  get: () => value.value?.alt ?? '',
  set: (text) => {
    value.value = { ...(value.value ?? {}), alt: text };
  },
});

// размер файла человеку, а не в байтах
const size = computed(() => {
  const bytes = value.value?.size ?? 0;

  return bytes > 1024 * 1024 ? (bytes / 1024 / 1024).toFixed(1) + ' МБ' : Math.round(bytes / 1024) + ' КБ';
});

async function remove() {
  if (!(await document.confirmDialog(t('feed_image_delete_ask')))) return;

  try {
    await apiFeed({ command: 'imageDelete', id: props.itemId, code: props.code });

    value.value = null;
  } catch (error) {}
}
</script>

<template>
  <div>
    <div v-if="value?.url" class="small text-muted mb-2">
      {{ value.mime }} · {{ value.x }} × {{ value.y }} · {{ size }}
    </div>

    <div v-else class="text-muted small mb-2">{{ t('feed_image_none') }}</div>

    <dataImageUpload
      :item-id="itemId"
      :code="code"
      :min-width="minWidth"
      :ratio="ratio"
      @uploaded="value = $event"
    />

    <button v-if="value?.url" class="btn btn-sm btn-outline-danger" @click="remove()">
      {{ t('delete') }}
    </button>

    <input
      v-model="alt"
      :disabled="!value?.url"
      :placeholder="'alt'"
      class="form-control form-control-sm mt-2"
    />
  </div>
</template>

<style scoped></style>
