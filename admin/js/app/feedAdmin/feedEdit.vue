<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { apiFeed } from './api.js';
import Help from '../CommonCom/Help.vue';
import InputText from 'primevue/inputtext';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
  feedId: { type: [String, Number], required: true },
});

// сколько строковых слотов есть в таблице
const STRING_SLOTS = 5;

const feedId = computed(() => Number(props.feedId));

const code = ref('');
const title = ref('');
const groupId = ref(null);
const itemsCount = ref(0); // есть записи — code полей уже не переименовать
const schema = ref({ version: 1, fields: [] }); // схема целиком, как пришла
const rows = ref([]); // поля группы String, с ними и работаем

function isStringColumn(column) {
  return typeof column === 'string' && column.startsWith('__string_');
}

async function load() {
  try {
    const feed = await apiFeed({ command: 'feedGet', id: feedId.value });

    code.value = feed.code;
    title.value = feed.title;
    groupId.value = feed.group_id;
    schema.value = feed.schema ?? { version: 1, fields: [] };

    rows.value = (schema.value.fields ?? [])
      .filter((field) => isStringColumn(field.column))
      .map((field) => ({
        column: field.column,
        code: field.code ?? '',
        label: field.label ?? '',
        default: field.default ?? '',
        unique: field.unique === true,
        validation: field.validation ?? '',
      }));

    // счётчик записей: он решает, можно ли менять code полей
    const list = await apiFeed({ command: 'itemsList', feedId: feedId.value, perPage: 1 });

    itemsCount.value = list.total;
  } catch (error) {}
}

// код и название ленты
async function saveFeed() {
  try {
    const feed = await apiFeed({
      command: 'feedSave',
      id: feedId.value,
      code: code.value.trim(),
      title: title.value.trim(),
    });

    code.value = feed.code;
    title.value = feed.title;

    document.showToast(t('saved'));
  } catch (error) {}
}

// новое поле в первый свободный строковый слот
function addRow() {
  const taken = rows.value.map((row) => row.column);

  for (let i = 1; i <= STRING_SLOTS; i++) {
    const column = '__string_' + i;

    if (!taken.includes(column)) {
      rows.value.push({
        column: column,
        code: '',
        label: '',
        default: '',
        unique: false,
        validation: '',
      });

      return;
    }
  }

  document.showToast(t('feed_no_free_slot'), 'error');
}

// строка таблицы -> поле схемы
function toField(row) {
  const field = {
    column: row.column,
    code: row.code.trim(),
    label: row.label.trim(),
  };

  if (row.default !== '' && row.default !== null) {
    field.default = row.default;
  }

  if (row.unique) {
    field.unique = true;
  }

  if (row.validation.trim() !== '') {
    field.validation = row.validation.trim();
  }

  return field;
}

/**
 * Схема уходит целиком, поэтому чужие группы переносятся как есть, на своих
 * местах: правим только строковые поля, новые дописываются в конец.
 */
async function saveSchema() {
  const byColumn = new Map(rows.value.map((row) => [row.column, row]));
  const fields = [];

  for (const field of schema.value.fields ?? []) {
    if (!isStringColumn(field.column)) {
      fields.push(field);

      continue;
    }

    const row = byColumn.get(field.column);

    if (row) {
      fields.push(toField(row));
      byColumn.delete(field.column);
    }
  }

  for (const row of byColumn.values()) {
    fields.push(toField(row));
  }

  try {
    schema.value = await apiFeed({
      command: 'schemaSave',
      feedId: feedId.value,
      schema: { version: schema.value.version ?? 1, fields: fields },
    });

    document.showToast(t('saved'));
  } catch (error) {}
}

watch(feedId, () => load());

onMounted(() => {
  load();
});
</script>

<template>
  <div>
    <h1 class="h4 mb-3">
      <RouterLink
        :to="groupId ? { name: 'feeds', params: { groupId: groupId } } : { name: 'groups' }"
        class="text-decoration-none me-2"
      >
        <i class="fas fa-arrow-left"></i>
      </RouterLink>
      {{ title }}
    </h1>

    <!-- код и название ленты -->
    <div class="row g-2 align-items-end mb-4" style="max-width: 44rem">
      <div class="col">
        <label class="form-label mb-0 small">{{ t('feed_code') }}</label>
        <InputText v-model="code" class="form-control form-control-sm" />
      </div>
      <div class="col">
        <label class="form-label mb-0 small">{{ t('feed_name') }}</label>
        <InputText v-model="title" class="form-control form-control-sm" />
      </div>
      <div class="col-auto">
        <button class="btn btn-sm btn-primary" @click="saveFeed()">{{ t('save') }}</button>
      </div>
    </div>

    <!-- строковые поля -->
    <div class="d-flex align-items-center mb-2">
      <h2 class="h6 mb-0 me-2">{{ t('feed_group_string') }}</h2>
      <Help :header="t('feed_group_string')" :text="t('feed_string_help')" />
    </div>

    <table class="table table-sm align-middle" style="max-width: 60rem">
      <thead>
        <tr>
          <th style="width: 12rem">
            Code
            <Help v-if="itemsCount > 0" header="Code" :text="t('feed_code_locked_help')" />
          </th>
          <th style="width: 12rem">Label</th>
          <th style="width: 8rem">default</th>
          <th style="width: 4rem">Unic</th>
          <th>validation</th>
          <th style="width: 6rem"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.column">
          <td>
            <InputText v-model="row.code" class="form-control form-control-sm" :disabled="itemsCount > 0" />
          </td>
          <td><InputText v-model="row.label" class="form-control form-control-sm" /></td>
          <td><InputText v-model="row.default" class="form-control form-control-sm" /></td>
          <td class="text-center"><input type="checkbox" v-model="row.unique" /></td>
          <td><InputText v-model="row.validation" class="form-control form-control-sm" /></td>
          <td class="text-muted small">
            <code>{{ row.column }}</code>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-sm btn-success" @click="addRow()">
        <i class="fas fa-plus me-1"></i>{{ t('add') }}
      </button>
      <button class="btn btn-sm btn-primary" @click="saveSchema()">{{ t('feed_save_schema') }}</button>
    </div>
  </div>
</template>

<style scoped></style>
