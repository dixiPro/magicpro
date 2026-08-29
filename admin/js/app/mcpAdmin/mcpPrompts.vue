<script setup>
import { ref, watch, nextTick } from 'vue';
import { apiMcp } from './api.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Промпты: то, что набирают агенту чаще одного раза.
 *
 * Список правится целиком и целиком же сохраняется: удалённая строка исчезает с
 * экрана, но из файла — только по «Сохранить». Поэтому подтверждения на
 * удаление нет: пока не сохранил, ничего не потеряно.
 */
const props = defineProps({
  prompts: { type: Array, default: () => [] },
});

const emit = defineEmits(['saved', 'close']);

// своя копия: правки не должны трогать список, из которого вставляют
const rows = ref([]);

watch(
  () => props.prompts,
  (list) => {
    rows.value = list.map((one) => ({ name: one.name, text: one.text }));

    // post: строки уже в DOM, иначе меряется пустое поле
    nextTick(() => grow());
  },
  { immediate: true }
);

function add() {
  // новый сверху: только что добавленное нужно править сейчас, а не искать внизу
  rows.value.unshift({ name: '', text: '' });

  nextTick(() => grow());
}

function remove(index) {
  rows.value.splice(index, 1);
}

async function save() {
  try {
    const data = await apiMcp({ command: 'promptsSave', prompts: rows.value });

    document.showToast(t('mcp_prompts_saved') + ': ' + data.prompts.length);

    emit('saved', data.prompts);
  } catch (error) {}
}

/** Высота по содержимому: промпт длиннее одной строки почти всегда. */
function grow(event) {
  const list = event ? [event.target] : document.querySelectorAll('.promptText');

  list.forEach((el) => {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
  });
}
</script>

<template>
  <div style="max-width: 60rem">
    <div class="d-flex gap-2 mb-3">
      <button class="btn btn-sm btn-outline-primary" @click="add()">
        <i class="fas fa-plus"></i>
        {{ t('mcp_prompt_add') }}
      </button>

      <button class="btn btn-sm btn-primary" @click="save()">{{ t('save') }}</button>

      <button class="btn btn-sm btn-outline-secondary ms-auto" @click="emit('close')">
        {{ t('mcp_close') }}
      </button>
    </div>

    <div v-if="!rows.length" class="text-muted">{{ t('mcp_prompts_empty') }}</div>

    <div v-for="(row, index) in rows" :key="index" class="row mb-2">
      <div class="col-3">
        <input
          v-model="row.name"
          class="form-control form-control-sm"
          :placeholder="t('mcp_prompt_name')"
        />
      </div>

      <div class="col-8">
        <textarea
          v-model="row.text"
          rows="2"
          class="form-control form-control-sm promptText"
          style="overflow: hidden; resize: none"
          :placeholder="t('mcp_prompt_text')"
          @input="grow($event)"
        ></textarea>
      </div>

      <div class="col-1">
        <button class="btn btn-sm btn-outline-danger" :title="t('delete')" @click="remove(index)">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
