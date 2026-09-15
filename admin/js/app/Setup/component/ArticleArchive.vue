<script setup>
import { ref, onMounted } from 'vue';
import { apiCall } from '../../apiCall.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const days = ref(0);
const deleted = ref(0);
const apiActive = ref(false);

async function apiArticles(data) {
  if (apiActive.value) return;
  try {
    apiActive.value = true;
    const response = await apiCall({
      url: '/a_dmin/api/articles',
      data: data,
      logResult: false,
    });
    return response.data;
  } catch (e) {
    document.showToast(e, 'error');
    throw new Error(t('error'));
  } finally {
    apiActive.value = false;
  }
}

async function load() {
  try {
    const res = await apiArticles({ command: 'archiveInfo' });
    days.value = res.days;
    deleted.value = res.deleted;
  } catch (e) {}
}

// версии удалённых статей не уходят сами: их сносит только эта кнопка
async function purge() {
  if (!(await document.confirmDialog(t('archive_purge_confirm')))) return;

  try {
    const res = await apiArticles({ command: 'archivePurgeDeleted' });
    document.showToast(t('archive_purged') + ': ' + res.gone);
    await load();
  } catch (e) {}
}

onMounted(load);
</script>

<template>
  <div class="my-3">
    <div class="small text-muted mb-1">
      {{ days ? t('archive_days_now', { days }) : t('archive_days_forever') }}
    </div>

    <button class="btn btn-sm btn-outline-secondary" :disabled="apiActive || !deleted" @click="purge">
      <i class="fas fa-broom"></i>
      {{ t('archive_purge') }}
      <span v-if="deleted">({{ deleted }})</span>
    </button>

    <span v-if="!deleted" class="ms-2 small text-muted">{{ t('archive_no_deleted') }}</span>
  </div>
</template>
