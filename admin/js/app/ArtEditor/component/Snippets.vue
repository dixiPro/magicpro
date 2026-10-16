<script setup>
/**
 * Свои заготовки блейда: кнопка вставляет заготовку в начало тела статьи,
 * синяя «Добавить» открывает имя и текст.
 *
 * Хранит их сервер — по файлу на заготовку в storage/app/private/magic/snippets,
 * команды snippet* в API статей. Здесь только список и правка.
 */
import { ref, watch } from 'vue';
import { useArticleStore } from '../store';
const store = useArticleStore();

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
  // панель открыта: по открытию список перечитывается
  visible: { type: Boolean, default: false },
});

// правка заготовки: null — форма закрыта, пустой oldName — заготовка новая
const edit = ref(null); // { name, text, oldName }

// имя заготовки — это имя файла, поэтому ничего, кроме латиницы, цифр,
// подчёркивания и дефиса
const nameOk = (name) => /^[A-Za-z0-9_-]+$/.test(String(name ?? '').trim());

watch(
  () => props.visible,
  (open) => {
    if (open) store.snippetList();
  },
  { immediate: true }
);

function add() {
  edit.value = { name: '', text: '', oldName: '' };
}

async function open(name) {
  const res = await store.snippetGet(name);

  if (res) edit.value = { name: res.name, text: res.text, oldName: res.name };
}

async function save() {
  if (!edit.value || !nameOk(edit.value.name)) return;

  const ok = await store.snippetSave(edit.value.name.trim(), edit.value.text, edit.value.oldName);

  if (ok) edit.value = null;
}

async function remove() {
  if (!edit.value?.oldName) return;

  await store.snippetDelete(edit.value.oldName);
  edit.value = null;
}
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <div v-for="name in store.snippets" :key="name" class="btn-group btn-group-sm">
        <button class="btn btn-success" @click="store.snippetInsert(name)">{{ name }}</button>
        <button class="btn btn-outline-success" :title="t('edit')" @click="open(name)"><i class="fas fa-pen"></i></button>
      </div>

      <button class="btn btn-primary btn-sm" @click="add">{{ t('snippet_add') }}</button>
    </div>

    <div v-if="edit" class="border rounded p-2 mt-2">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <input v-model="edit.name" type="text" class="form-control form-control-sm" style="width: 240px" :placeholder="t('snippet_name')" />

        <button class="btn btn-success btn-sm" :disabled="!nameOk(edit.name)" @click="save">{{ t('save') }}</button>
        <button v-if="edit.oldName" class="btn btn-danger btn-sm" @click="remove">{{ t('delete') }}</button>
        <button class="btn btn-secondary btn-sm" @click="edit = null">{{ t('cancel') }}</button>

        <small v-if="edit.name && !nameOk(edit.name)" class="text-danger">{{ t('snippet_name_rule') }}</small>
      </div>

      <textarea v-model="edit.text" class="form-control" rows="10" spellcheck="false"></textarea>
    </div>

    <div v-else class="mt-2"><small class="text-muted">{{ t('snippet_hint') }}</small></div>
  </div>
</template>
