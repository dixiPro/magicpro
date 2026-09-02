<script setup>
import { ref, onMounted } from 'vue';
import { apiCall } from '../apiCall.js';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

// файл никуда не заливается: он живёт здесь и уходит на сервер каждым проходом
const fileName = ref('');
const text = ref('');

const mode = ref('partial');
const snapshot = ref(true);

const report = ref([]);
const ready = ref(false); // проверка прошла и она чистая

const snapshots = ref([]);
const stateName = ref('');
const apiActive = ref(false);

const URL = '/a_dmin/api/import';

async function apiImport(data) {
  if (apiActive.value) return;
  try {
    apiActive.value = true;
    const response = await apiCall({
      url: URL,
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

function pickFile(event) {
  const file = event.target.files?.[0];

  report.value = [];
  ready.value = false;
  text.value = '';
  fileName.value = file ? file.name : '';

  if (!file) return;

  const reader = new FileReader();
  reader.onload = () => {
    text.value = String(reader.result ?? '');
  };
  reader.readAsText(file);
}

// первый проход: ничего не меняет, показывает что будет
async function check() {
  if (!text.value) {
    document.showToast(t('imp_no_file'), 'error');
    return;
  }

  try {
    const res = await apiImport({ command: 'check', mode: mode.value, text: text.value });
    report.value = res.report;
    ready.value = res.ok;
    document.showToast(res.ok ? t('imp_report_ok') : t('imp_report_bad'), res.ok ? 'success' : 'error');
  } catch (e) {}
}

// второй проход: те же проверки и работа
async function run() {
  if (!(await document.confirmDialog(t('imp_run_ask')))) return;

  try {
    const res = await apiImport({
      command: 'run',
      mode: mode.value,
      text: text.value,
      snapshot: snapshot.value,
    });
    report.value = res.report;
    ready.value = false;
    document.showToast(res.ok ? t('imp_done_all') : t('imp_report_bad'), res.ok ? 'success' : 'error');
    await loadSnapshots();
  } catch (e) {}
}

async function loadSnapshots() {
  try {
    const res = await apiImport({ command: 'snapshots' });
    snapshots.value = res.snapshots;
  } catch (e) {}
}

// имя предлагаем датой, дальше его правят руками
function defaultName() {
  const d = new Date();
  const two = (n) => String(n).padStart(2, '0');
  return `root_${d.getFullYear()}-${two(d.getMonth() + 1)}-${two(d.getDate())}_${two(d.getHours())}-${two(d.getMinutes())}`;
}

async function saveState() {
  const name = stateName.value.trim() || defaultName();

  // имя своё, значит и совпасть может: молча затирать чужой снимок нельзя
  const taken = snapshots.value.some((row) => row.file === name + '.json');
  if (taken && !(await document.confirmDialog(t('imp_state_taken') + ' ' + name + '.json'))) return;

  try {
    const res = await apiImport({ command: 'save', name: name });
    snapshots.value = res.snapshots;
    stateName.value = defaultName();
    document.showToast(t('imp_saved') + ': ' + res.file);
  } catch (e) {}
}

async function removeState(row) {
  if (!(await document.confirmDialog(t('imp_delete_ask') + ' ' + row.file))) return;

  try {
    const res = await apiImport({ command: 'delete', file: row.file });
    snapshots.value = res.snapshots;
    document.showToast(t('imp_deleted'));
  } catch (e) {}
}

async function restoreState(row) {
  if (!(await document.confirmDialog(t('imp_restore_ask') + ' ' + row.file))) return;

  try {
    const res = await apiImport({ command: 'restore', file: row.file });
    report.value = res.report;
    ready.value = false;
    snapshots.value = res.snapshots;
    document.showToast(res.ok ? t('imp_done_all') : t('imp_report_bad'), res.ok ? 'success' : 'error');
  } catch (e) {}
}

function download(row) {
  window.location.href = '/a_dmin/api/importSnapshot?file=' + encodeURIComponent(row.file);
}

// строку отчёта собирает словарь: с сервера едет код и его части
function line(row) {
  return t('imp_' + row.code, row.params ?? {});
}

function size(bytes) {
  return Math.max(1, Math.round(bytes / 1024)) + ' KB';
}

onMounted(() => {
  stateName.value = defaultName();
  loadSnapshots();
});
</script>

<template>
  <div class="my-3">
    <TosatConfirm />

    <div class="p-3 border rounded">
      <div class="mb-3">
        <label class="form-label fw-bold">{{ t('imp_file') }}</label>
        <input type="file" accept=".json" class="form-control form-control-sm" @change="pickFile" />
      </div>

      <div class="mb-3">
        <div class="fw-bold">{{ t('imp_mode') }}</div>

        <div class="form-check">
          <input class="form-check-input" type="radio" id="modePartial" value="partial" v-model="mode" />
          <label class="form-check-label" for="modePartial">{{ t('imp_partial') }}</label>
        </div>

        <div class="form-check">
          <input class="form-check-input" type="radio" id="modeFull" value="full" v-model="mode" />
          <label class="form-check-label" for="modeFull">{{ t('imp_full') }}</label>
        </div>

        <div class="form-text">{{ t('imp_mode_hint') }}</div>
        <div v-if="mode === 'partial'" class="form-text">{{ t('imp_trees_hint') }}</div>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="snapshotBefore" v-model="snapshot" />
        <label class="form-check-label" for="snapshotBefore">{{ t('imp_snapshot_before') }}</label>
      </div>

      <button class="btn btn-sm btn-primary" :disabled="apiActive" @click="check">
        <i class="fas fa-search"></i>
        {{ t('imp_check') }}
      </button>

      <button class="btn btn-sm btn-danger ms-2" :disabled="!ready || apiActive" @click="run">
        <i class="fas fa-file-import"></i>
        {{ t('imp_run') }}
      </button>

      <span v-if="!ready" class="ms-2 text-muted small">{{ t('imp_check_first') }}</span>
    </div>

    <div v-if="ready && !report.length" class="mt-3 text-success">{{ t('imp_clean') }}</div>

    <!-- только ошибки и коллизии; после второго прохода рядом «сделано» -->
    <div v-if="report.length" class="table-responsive mt-3">
      <table class="table table-sm align-top small">
        <tbody>
          <tr v-for="(row, index) in report" :key="index">
            <td :class="{ 'text-danger': row.error }">{{ line(row) }}</td>
            <td class="text-nowrap text-end">
              <span v-if="row.done" class="text-success">{{ t('imp_done') }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <h2 class="h5 mt-4">{{ t('imp_states') }}</h2>

    <div class="my-2 d-flex align-items-center gap-2 flex-wrap">
      <input
        v-model="stateName"
        type="text"
        class="form-control form-control-sm"
        style="max-width: 22rem"
        :placeholder="t('imp_state_name')"
        @keyup.enter="saveState"
      />

      <button class="btn btn-sm btn-outline-secondary text-nowrap" :disabled="apiActive" @click="saveState">
        <i class="fas fa-save"></i>
        {{ t('imp_save_state') }}
      </button>

      <span class="text-muted small">{{ t('imp_state_name_hint') }}</span>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle small">
        <thead>
          <tr>
            <th>{{ t('imp_file') }}</th>
            <th>{{ t('imp_date') }}</th>
            <th>{{ t('imp_size') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in snapshots" :key="row.file">
            <td v-text="row.file"></td>
            <td v-text="row.date"></td>
            <td v-text="size(row.size)"></td>
            <td class="text-nowrap text-end">
              <button class="btn btn-sm btn-outline-secondary" :title="t('imp_download')" @click="download(row)">
                <i class="fas fa-download"></i>
              </button>
              <button class="btn btn-sm btn-outline-secondary ms-1" :title="t('imp_restore')" :disabled="apiActive" @click="restoreState(row)">
                <i class="fas fa-undo"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger ms-1" :title="t('imp_delete')" :disabled="apiActive" @click="removeState(row)">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>

          <tr v-if="!snapshots.length">
            <td colspan="4" class="text-center text-muted">{{ t('imp_states_empty') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
