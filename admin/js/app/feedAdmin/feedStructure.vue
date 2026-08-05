<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useFeedStore } from './store.js';

import fieldsDate from './fieldsDate.vue';
import fieldsLink from './fieldsLink.vue';
import fieldsBool from './fieldsBool.vue';
import fieldsString from './fieldsString.vue';
import fieldsBigint from './fieldsBigint.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const store = useFeedStore();

async function saveSchema() {
  try {
    await store.saveSchema();
    document.showToast(t('saved'));
  } catch (error) {
    // кривой JSON до сервера не доходит, поэтому отличаем его от отказа API
    if (error instanceof SyntaxError || error.message === 'data must be an array') {
      document.showToast(t('feed_json_bad'), 'error');
    }
  }
}

function addDataField(type) {
  if (!store.addDataField(type)) {
    document.showToast(t('feed_json_bad'), 'error');
  }
}

// привычное сохранение с клавиатуры.
// event.code, а не event.key: код клавиши не зависит от раскладки, в русской
// key приходит как «ы»
function onKeyDown(event) {
  if (!(event.ctrlKey || event.metaKey) || event.code !== 'KeyS') return;

  event.preventDefault();

  if (store.dirty) {
    saveSchema();
  }
}

onMounted(() => {
  window.addEventListener('keydown', onKeyDown, { capture: true });
});

onUnmounted(() => {
  window.removeEventListener('keydown', onKeyDown, { capture: true });
});
</script>

<template>
  <div>
    <!-- поля по типам, порядок из ТЗ -->
    <fieldsDate />
    <fieldsLink />
    <fieldsBool />
    <fieldsString />
    <fieldsBigint />

    <h2 class="h6 mb-2">
      JSON
      <span class="text-muted small fw-normal">__data → data</span>
    </h2>

    <textarea
      v-model="store.jsonText"
      rows="10"
      spellcheck="false"
      class="form-control form-control-sm font-monospace mb-2"
      style="max-width: 60rem"
    ></textarea>

    <!-- заготовки: тип дописывается в конец массива готовым куском -->
    <div class="mb-4">
      <button
        v-for="type in store.DATA_TYPES"
        :key="type"
        class="btn btn-sm btn-outline-secondary me-1 mb-1"
        @click="addDataField(type)"
      >
        {{ type }}
      </button>
    </div>

    <button v-if="store.dirty" class="btn btn-sm btn-primary" @click="saveSchema()">
      {{ t('feed_save_schema') }} <span class="small opacity-75">Ctrl+S</span>
    </button>
  </div>
</template>

<style scoped></style>
