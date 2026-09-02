<script setup>
import { ref, onMounted } from 'vue';
import { apiCall } from '../../apiCall.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const reports = ref([]);
const apiActive = ref(false);

async function apiCleanup(data) {
  if (apiActive.value) return;
  try {
    apiActive.value = true;
    const response = await apiCall({
      url: '/a_dmin/api/cleanup',
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

// press and it goes: finds, repairs, writes the report
async function run() {
  try {
    const res = await apiCleanup({ command: 'run' });
    reports.value = res.reports;
    document.showToast(res.found ? t('check_done') + ': ' + res.found : t('check_clean'));
  } catch (e) {}
}

async function loadReports() {
  try {
    const res = await apiCleanup({ command: 'reports' });
    reports.value = res.reports;
  } catch (e) {}
}

function open(row) {
  window.open('/a_dmin/api/cleanupReport?file=' + encodeURIComponent(row.file), '_blank');
}

onMounted(() => {
  loadReports();
});
</script>

<template>
  <div class="my-3">
    <button class="btn btn-sm btn-outline-secondary" :disabled="apiActive" @click="run">
      <i class="fas fa-stethoscope"></i>
      {{ t('check_articles') }}
    </button>

    <span class="ms-2 text-muted small">{{ t('check_hint') }}</span>

    <div class="mt-2 small">
      <span class="text-muted">{{ t('check_reports') }}:</span>

      <a v-for="row in reports" :key="row.file" href="#" class="ms-2" @click.prevent="open(row)">
        {{ row.date }}
      </a>

      <span v-if="!reports.length" class="ms-2 text-muted">{{ t('check_reports_empty') }}</span>
    </div>
  </div>
</template>
