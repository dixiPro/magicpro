<script setup>
import { ref, onMounted } from 'vue';
import { apiCall } from '../apiCall.js';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';
import ModalWindow from '../CommonCom/ModalWindow.vue';
import { formatDate } from '../CommonCom/formatDate.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const tasks = ref([]);
const apiActive = ref(false);

// пустая форма: она же образец полей
const emptyForm = () => ({
  id: 0,
  name: '',
  controller: '',
  params: '',
  cron: '',
  enabled: true,
});

const form = ref(emptyForm());
const formShow = ref(false);

// эту функцию использовать для обращения к АПИ
async function apiCron(data) {
  const url = '/a_dmin/api/cron';
  if (apiActive.value) return;
  try {
    apiActive.value = true;
    const response = await apiCall({
      url: url,
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

async function loadList() {
  try {
    const res = await apiCron({ command: 'list' });
    tasks.value = res.list;
  } catch (error) {}
}

// параметры хранятся объектом, а правятся строкой
function openForm(task = null) {
  form.value = task
    ? {
        id: task.id,
        name: task.name,
        controller: task.controller,
        params: task.params ? JSON.stringify(task.params) : '',
        cron: task.cron,
        enabled: !!task.enabled,
      }
    : emptyForm();

  formShow.value = true;
}

async function saveTask() {
  try {
    await apiCron({
      command: 'save',
      id: form.value.id,
      name: form.value.name,
      controller: form.value.controller,
      params: form.value.params,
      cron: form.value.cron,
      enabled: form.value.enabled,
    });
    formShow.value = false;
    document.showToast(t('cron_saved'));
    await loadList();
  } catch (error) {}
}

async function toggleTask(task) {
  try {
    await apiCron({ command: 'toggle', id: task.id });
    await loadList();
  } catch (error) {}
}

async function deleteTask(task) {
  if (!(await document.confirmDialog(t('cron_delete_confirm') + ' ' + task.name))) return;
  try {
    await apiCron({ command: 'delete', id: task.id });
    document.showToast(t('cron_deleted'));
    await loadList();
  } catch (error) {}
}

// ручной запуск: идёт мимо расписания и ждёт ответа, поэтому долгая задача
// держит кнопку до конца
async function runNow(task) {
  try {
    const res = await apiCron({ command: 'runNow', id: task.id });
    document.showToast(t('cron_done') + ': ' + res.ms + ' ms');
    await loadList();
  } catch (error) {}
}

onMounted(() => {
  loadList();
});
</script>

<template>
  <!-- не container: он центрируется и съедает ширину, отступы даёт шаблон админки -->
  <div class="my-3">
    <TosatConfirm />

    <div class="my-3">
      <button class="btn btn-sm btn-primary" @click="openForm()">
        <i class="fas fa-plus"></i>
        {{ t('cron_add') }}
      </button>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-top small">
        <thead>
          <tr>
            <th>{{ t('cron_name') }}</th>
            <th>{{ t('cron_controller') }}</th>
            <th>{{ t('cron_params') }}</th>
            <th>{{ t('cron_expression') }}</th>
            <th>{{ t('cron_enabled') }}</th>
            <th>{{ t('cron_last_run') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="task in tasks" :key="task.id">
            <td v-text="task.name"></td>
            <td>
              <span v-text="task.controller"></span>
              <!-- статью могли переименовать или удалить уже после того, как
                   задача была заведена: проверка идёт на каждом заходе -->
              <div v-if="!task.check.ok" class="text-danger">
                <i class="fas fa-exclamation-triangle"></i>
                {{ t('cron_broken') }}: <span v-text="task.check.error"></span>
              </div>
            </td>
            <td>
              <code v-if="task.params" v-text="JSON.stringify(task.params)"></code>
            </td>
            <td><code v-text="task.cron"></code></td>
            <td>
              <button class="btn btn-sm btn-link p-0" @click="toggleTask(task)">
                <i :class="task.enabled ? 'fas fa-toggle-on text-success' : 'fas fa-toggle-off text-muted'"></i>
              </button>
            </td>
            <td v-text="formatDate(task.last_run_at)"></td>
            <td class="text-nowrap text-end">
              <button class="btn btn-sm btn-outline-secondary" :title="t('cron_run_now')" @click="runNow(task)">
                <i class="fas fa-play"></i>
              </button>
              <button class="btn btn-sm btn-outline-secondary ms-1" :title="t('edit')" @click="openForm(task)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger ms-1" :title="t('delete')" @click="deleteTask(task)">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <!--  -->
          <tr v-if="!tasks.length">
            <td colspan="7" class="text-center text-muted">
              {{ t('cron_empty') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- форма -->
    <ModalWindow v-model:visible="formShow" :header="form.id ? t('cron_form_edit') : t('cron_form_new')" :initial-width="700" :initial-height="520">
      <div class="mb-3">
        <label class="form-label">{{ t('cron_name') }}</label>
        <input v-model="form.name" type="text" class="form-control form-control-sm" />
      </div>

      <div class="mb-3">
        <label class="form-label">{{ t('cron_controller') }}</label>
        <input v-model="form.controller" type="text" class="form-control form-control-sm" />
      </div>

      <div class="mb-3">
        <label class="form-label">{{ t('cron_params') }}</label>
        <textarea v-model="form.params" rows="3" class="form-control form-control-sm"></textarea>
        <div class="form-text">{{ t('cron_params_hint') }}</div>
      </div>

      <div class="mb-3">
        <label class="form-label">{{ t('cron_expression') }}</label>
        <input v-model="form.cron" type="text" class="form-control form-control-sm" placeholder="0 4 * * *" />
        <div class="form-text">{{ t('cron_expression_hint') }}</div>
      </div>

      <div class="form-check mb-3">
        <input v-model="form.enabled" class="form-check-input" type="checkbox" id="cronEnabled" />
        <label class="form-check-label" for="cronEnabled">{{ t('cron_enabled') }}</label>
      </div>

      <div class="text-end">
        <button class="btn btn-sm btn-outline-secondary" @click="formShow = false">{{ t('cancel') }}</button>
        <button class="btn btn-sm btn-primary ms-2" @click="saveTask">{{ t('save') }}</button>
      </div>
    </ModalWindow>
  </div>
</template>
