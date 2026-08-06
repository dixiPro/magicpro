<script setup>
import { ref } from 'vue';
import { useFeedStore } from './store.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Колонки списка записей: что в нём есть, чего в нём нет и в каком порядке.
 *
 * Поля берутся такими, какими они сейчас на экране структуры, а не какими
 * сохранены: сняли флаг — строка тут же переехала вниз.
 *
 * Перетаскивание делает две вещи сразу. Внутри верхней таблицы это порядок
 * колонок, между таблицами — тот же showOnList, что и галочка в структуре.
 * Отдельной кнопки «убрать» поэтому нет: строку просто уносят вниз.
 */
const store = useFeedStore();

const dragged = ref('');

function onDrop(toList, index) {
  if (dragged.value === '') return;

  store.moveField(dragged.value, toList, index);

  dragged.value = '';
}

async function saveSchema() {
  try {
    await store.saveSchema();
    document.showToast(t('saved'));
  } catch (error) {
    if (error instanceof SyntaxError || error.message === 'data must be an array') {
      document.showToast(t('feed_json_bad'), 'error');
    }
  }
}
</script>

<template>
  <div>
    <div class="mb-4" @dragover.prevent @drop="onDrop(true, store.inList.length)">
      <h2 class="h6 mb-2">in list</h2>

      <table class="table table-sm align-middle" style="max-width: 40rem">
        <thead>
          <tr>
            <th style="width: 8rem">Type</th>
            <th style="width: 12rem">Code</th>
            <th>Label</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(field, index) in store.inList"
            :key="field.name"
            draggable="true"
            @dragstart="dragged = field.name"
            @dragover.prevent
            @drop.stop="onDrop(true, index)"
          >
            <td>
              <i class="fas fa-grip-vertical text-muted me-2"></i>
              <code>{{ field.type }}</code>
            </td>
            <td>{{ field.code }}</td>
            <td>{{ field.label }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mb-4" @dragover.prevent @drop="onDrop(false, 0)">
      <h2 class="h6 mb-2">not in list</h2>

      <table class="table table-sm align-middle" style="max-width: 40rem">
        <thead>
          <tr>
            <th style="width: 8rem">Type</th>
            <th style="width: 12rem">Code</th>
            <th>Label</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="field in store.notInList"
            :key="field.name"
            draggable="true"
            @dragstart="dragged = field.name"
          >
            <td>
              <i class="fas fa-grip-vertical text-muted me-2"></i>
              <code>{{ field.type }}</code>
            </td>
            <td>{{ field.code }}</td>
            <td>{{ field.label }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <button v-if="store.dirty" class="btn btn-sm btn-primary" @click="saveSchema()">
      {{ t('feed_save_schema') }}
    </button>
  </div>
</template>

<style scoped>
th {
  font-weight: normal;
  font-size: 0.9rem;
}

tbody tr {
  cursor: move;
}
</style>
