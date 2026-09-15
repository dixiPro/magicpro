<script setup>
import { ref, watch } from 'vue';
import { apiArt } from '../../apiCall';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { useArticleStore } from '../store';
const store = useArticleStore();

const versions = ref([]);

// открытая версия: пока её нет, показывается список
const opened = ref(null);
const apiActive = ref(false);

async function api(data) {
  if (apiActive.value) return;
  try {
    apiActive.value = true;
    return await apiArt(data);
  } catch (e) {
    document.showToast(e, 'error');
    throw new Error(t('error'));
  } finally {
    apiActive.value = false;
  }
}

async function load() {
  opened.value = null;

  try {
    const res = await api({ command: 'versions', id: store.article.id });
    versions.value = res.versions;
  } catch (e) {}
}

async function open(row) {
  try {
    opened.value = await api({ command: 'versionGet', versionId: row.id });
  } catch (e) {}
}

// восстановление — обычное сохранение: файлы перегенерируются, а сама
// восстановленная версия ложится в архив сегодняшним числом
async function restore() {
  if (!(await document.confirmDialog(t('archive_restore_confirm')))) return;

  try {
    await api({ command: 'versionRestore', versionId: opened.value.id });
    await store.loadRec(store.article.id);
    store.statusArchive = false;
    document.showToast(t('archive_restored'));
  } catch (e) {}
}

// список перечитывается на каждом открытии: за время работы версий прибавилось
watch(
  () => store.statusArchive,
  (open) => {
    if (open) load();
  },
);
</script>

<template>
  <Dialog v-model:visible="store.statusArchive" modal :header="t('archive')" :style="{ width: '70rem' }">
    <div v-if="!opened">
      <div v-if="!versions.length" class="text-muted">{{ t('archive_empty') }}</div>

      <table v-else class="table table-sm table-hover small">
        <thead>
          <tr>
            <th>{{ t('archive_when') }}</th>
            <th>{{ t('archive_who') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in versions" :key="row.id" class="pointer" @click="open(row)">
            <td>{{ row.created_at }}</td>
            <td>{{ row.user || row.userId || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else>
      <div class="small text-muted mb-2">
        {{ opened.created_at }} · {{ opened.article.name }} · {{ opened.article.title }}
      </div>

      <div class="mb-2">
        <label class="form-label small fw-bold">{{ t('archive_body') }}</label>
        <textarea class="form-control font-monospace small" rows="12" readonly :value="opened.article.body"></textarea>
      </div>

      <div class="mb-2">
        <label class="form-label small fw-bold">{{ t('archive_controller') }}</label>
        <textarea
          class="form-control font-monospace small"
          rows="10"
          readonly
          :value="opened.article.controller"
        ></textarea>
      </div>

      <div class="form-text">{{ t('archive_copy_hint') }}</div>
    </div>

    <template #footer>
      <button v-if="opened" class="btn btn-sm btn-secondary" @click="opened = null">
        {{ t('archive_back') }}
      </button>

      <button v-if="opened" class="btn btn-sm btn-danger" :disabled="apiActive" @click="restore">
        <i class="fas fa-archive"></i>
        {{ t('archive_restore') }}
      </button>

      <button class="btn btn-sm btn-outline-secondary" @click="store.statusArchive = false">
        {{ t('archive_exit') }}
      </button>
    </template>
  </Dialog>
</template>
