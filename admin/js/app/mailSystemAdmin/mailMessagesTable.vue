<script setup>
import { ref, computed, onMounted } from 'vue';
import { apiCall } from '../apiCall.js';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';
import ModalWindow from '../CommonCom/ModalWindow.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const COUNT = 30;

const section = ref('sent'); // 'sent' | 'queue'
const search = ref('');
const messages = ref([]);
const total = ref(0);
const offset = ref(0);
const apiActive = ref(false);

let searchTimer = null; // таймер debounce для поля search

const errorDialog = ref({
  show: false,
  subject: '',
  errors: [],
});

const detailsDialog = ref({
  show: false,
  message: null,
});

const rawDialog = ref({
  show: false,
  raw_message: '',
});

// показывать ли колонку scheduled_at (только для раздела «В очереди»)
const isQueue = computed(() => section.value === 'queue');
// есть ли ещё письма для подгрузки
const canLoadMore = computed(() => messages.value.length < total.value);

// эту функцию использовать для обращения к АПИ
async function apiMail(data) {
  const url = '/a_dmin/api/mailSystem';
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

// загрузить список писем текущего раздела
// reset = true — начать список заново, false — подгрузить следующую порцию
async function loadList(reset = true) {
  if (reset) {
    offset.value = 0;
    messages.value = [];
  }
  try {
    const res = await apiMail({
      command: 'messagesList',
      section: section.value,
      search: search.value.trim(),
      count: COUNT,
      offset: offset.value,
    });
    messages.value.push(...res.messages);
    total.value = res.total;
    offset.value = messages.value.length;
  } catch (error) {}
}

// переключение раздела
function switchSection(name) {
  if (section.value === name) return;
  section.value = name;
  loadList(true);
}

// ввод в поле search: автоматический поиск с debounce
function onSearch() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    loadList(true);
  }, 300);
}

// формат даты: 2026-07-18T15:09:24.000000Z -> 2026-07-18 15:09:24
function formatDate(value) {
  if (!value) return '';
  const m = String(value).match(/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2}:\d{2})/);
  return m ? `${m[1]} ${m[2]}` : value;
}

// формат даты для колонки "Отправлено": без секунд, год — только если не текущий
// 2026-07-18T15:09:24.000000Z -> 07-18 15:09 (текущий год) или 25-07-18 15:09 (иначе)
function formatDateShort(value) {
  if (!value) return '';
  const m = String(value).match(/^(\d{4})-(\d{2}-\d{2})[T ](\d{2}:\d{2})/);
  if (!m) return value;
  const currentYear = String(new Date().getFullYear());
  return m[1] === currentYear ? `${m[2]} ${m[3]}` : `${m[1].slice(2)}-${m[2]} ${m[3]}`;
}

// дата статуса относительно даты отправки/планирования:
// год совпадает -> без года; год и месяц-день совпадают -> только время
function formatStatusDate(statusValue, referenceValue) {
  if (!statusValue) return '';
  const s = String(statusValue).match(/^(\d{4})-(\d{2}-\d{2})[T ](\d{2}:\d{2})/);
  if (!s) return statusValue;
  const r = referenceValue ? String(referenceValue).match(/^(\d{4})-(\d{2}-\d{2})/) : null;

  if (r && s[1] === r[1] && s[2] === r[2]) return s[3];
  if (r && s[1] === r[1]) return `${s[2]} ${s[3]}`;
  return `${s[1].slice(2)}-${s[2]} ${s[3]}`;
}

// статусы письма -> иконка + класс цвета
const MAIL_STATUS_ICONS = {
  queued: { icon: 'fa-clock', class: 'text-secondary' },
  sent: { icon: 'fa-envelope', class: 'text-secondary' },
  delivered: { icon: 'fa-check-circle', class: 'text-success' },
  open: { icon: 'fa-envelope-open-text', class: 'text-info' },
  error: { icon: 'fa-exclamation-triangle', class: 'text-warning' },
  failed: { icon: 'fa-times-circle', class: 'text-danger' },
  emailblocked: { icon: 'fa-ban', class: 'text-danger' },
};

const MAIL_STATUSES = Object.keys(MAIL_STATUS_ICONS);

// перевод статуса письма
function statusLabel(status) {
  return MAIL_STATUS_ICONS[status] ? t('mail_status_' + status) : status;
}

// иконка + класс цвета для статуса письма
function statusIcon(status) {
  return MAIL_STATUS_ICONS[status] || { icon: 'fa-question-circle', class: 'text-muted' };
}

// открыть модалку с историей ошибок письма
function openErrors(message) {
  errorDialog.value = {
    show: true,
    subject: message.subject,
    errors: Array.isArray(message.errors) ? message.errors : [],
  };
}

// открыть модалку с содержимым письма
function openDetails(message) {
  detailsDialog.value = {
    show: true,
    message,
  };
}

// открыть модалку с raw-письмом (из модалки содержимого)
function openRaw() {
  rawDialog.value = {
    show: true,
    raw_message: detailsDialog.value.message?.raw_message || '',
  };
}

// удалить письмо (в обоих разделах) по id с подтверждением
async function removeMessage(index) {
  const message = messages.value[index];
  const ok = await document.confirmDialog(t('mail_delete_confirm'));
  if (!ok) return;
  try {
    await apiMail({
      command: 'deleteEmail',
      id: message.id,
    });
    messages.value.splice(index, 1);
    total.value = Math.max(0, total.value - 1);
    offset.value = messages.value.length;
    document.showToast(t('mail_deleted'));
  } catch (error) {}
}

onMounted(() => {
  loadList(true);
});
</script>

<template>
  <div>
    <!-- разделы -->
    <div class="d-flex align-items-start gap-2">
      <div class="btn-group my-3 flex-shrink-0" role="group">
        <button type="button" class="btn btn-sm text-nowrap" :class="section === 'sent' ? 'btn-primary' : 'btn-outline-primary'" @click="switchSection('sent')">
          {{ t('mail_sent') }}
        </button>

        <button
          type="button"
          class="btn btn-sm text-nowrap"
          :class="section === 'queue' ? 'btn-primary' : 'btn-outline-primary'"
          @click="switchSection('queue')"
        >
          {{ t('mail_queue') }}
        </button>
      </div>

      <div class="flex-grow-1 my-3">
        <input v-model="search" type="text" class="form-control form-control-sm" :placeholder="t('search')" @input="onSearch" />
      </div>
    </div>

    <!-- список -->
    <div class="table-responsive">
      <table class="table table-sm align-top small">
        <thead>
          <tr>
            <!-- <th>{{ t('mail_from') }}</th> -->
            <th>{{ t('mail_to') }}</th>
            <th>{{ t('mail_subject') }}</th>
            <th v-if="!isQueue">{{ t('mail_sent_at') }}</th>
            <th v-if="isQueue">{{ t('mail_scheduled_at') }}</th>
            <th class="text-center"><i class="fas fa-sync-alt" :title="t('mail_attempts')"></i></th>
            <th>{{ t('mail_status') }}</th>
            <th class="text-center"></th>
            <th class="text-center"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(message, index) in messages" :key="message.id">
            <!-- <td v-text="message.from_email"></td> -->
            <td v-text="message.to_email"></td>
            <td class="pointer text-primary" v-text="message.subject" @click="openDetails(message)"></td>
            <td v-if="!isQueue" class="text-nowrap" v-text="formatDateShort(message.sent_at)"></td>
            <td v-if="isQueue" class="text-nowrap" v-text="formatDateShort(message.scheduled_at)"></td>
            <td class="text-center" v-text="message.attempts"></td>
            <td class="text-nowrap">
              <i :class="['fas', statusIcon(message.status).icon, statusIcon(message.status).class]" :title="statusLabel(message.status)"></i>
              <span class="text-muted small">{{ formatStatusDate(message.updated_at, isQueue ? message.scheduled_at : message.sent_at) }}</span>
            </td>
            <td class="text-center">
              <i
                v-if="message.errors && message.errors.length"
                class="fas fa-exclamation-triangle text-danger pointer"
                :title="t('mail_errors_title')"
                @click="openErrors(message)"
              ></i>
            </td>
            <td class="text-center">
              <i class="fas fa-trash text-danger pointer" :title="t('delete')" @click="removeMessage(index)"></i>
            </td>
          </tr>
          <!--  -->
          <tr v-if="!messages.length">
            <td colspan="8" class="text-center text-muted">
              {{ t('mail_empty') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- подгрузка -->
    <div class="my-3 text-center" v-if="canLoadMore">
      <button class="btn btn-sm btn-outline-secondary" @click="loadList(false)">{{ t('mail_load_more') }} ({{ messages.length }} / {{ total }})</button>
    </div>

    <!-- пояснение иконок статусов -->
    <div class="d-flex flex-wrap gap-3 my-2 small text-muted">
      <div v-for="status in MAIL_STATUSES" :key="status" class="d-flex align-items-center gap-1">
        <i :class="['fas', statusIcon(status).icon, statusIcon(status).class]"></i>
        <span>{{ statusLabel(status) }}</span>
      </div>
    </div>

    <!-- модалка с историей ошибок -->
    <Dialog v-model:visible="errorDialog.show" :header="t('mail_errors_title')" modal style="width: 600px; max-width: 95vw">
      <!-- <div class="mb-2 fw-bold" v-text="errorDialog.subject"></div> -->
      <div v-if="!errorDialog.errors.length" class="text-muted small">
        {{ t('mail_no_errors') }}
      </div>
      <ul v-else class="list-unstyled mb-0 small">
        <li v-for="(err, i) in errorDialog.errors" :key="i" class="mb-2">
          <span class="text-muted me-2" v-text="formatDate(err.ts)"></span>
          <span v-text="err.message"></span>
        </li>
      </ul>
    </Dialog>

    <!-- модалка с содержимым письма -->
    <ModalWindow v-model:visible="detailsDialog.show" :header="t('mail_details_title')" :initial-width="700" :initial-height="550">
      <template v-if="detailsDialog.message">
        <div class="mb-3">
          <button class="btn btn-sm btn-outline-secondary" @click="openRaw">
            {{ t('mail_details_button') }}
          </button>
        </div>
        <div class="mb-2">
          <span class="fw-bold">{{ t('mail_from') }}:</span>
          <span v-text="detailsDialog.message.from_email"></span>
          <span v-if="detailsDialog.message.from_name"> / <span v-text="detailsDialog.message.from_name"></span></span>
        </div>
        <div class="mb-2">
          <span class="fw-bold">{{ t('mail_to') }}:</span>
          <span v-text="detailsDialog.message.to_email"></span>
        </div>
        <div class="mb-2">
          <span class="fw-bold">{{ t('mail_reply_to') }}:</span>
          <span v-text="detailsDialog.message.reply_to"></span>
        </div>
        <div class="mb-2">
          <span class="fw-bold">{{ t('mail_subject') }}:</span>
          <span v-text="detailsDialog.message.subject"></span>
        </div>
        <hr />
        <div v-html="detailsDialog.message.html"></div>
        <div class="mt-3 text-end">
          <button class="btn btn-sm btn-secondary" @click="detailsDialog.show = false">
            {{ t('mail_close') }}
          </button>
        </div>
      </template>
    </ModalWindow>

    <!-- модалка с raw-письмом -->
    <ModalWindow v-model:visible="rawDialog.show" :header="t('mail_raw_title')" :z-index="2100" :initial-width="700" :initial-height="550">
      <pre class="mb-0" v-text="rawDialog.raw_message"></pre>
    </ModalWindow>

    <TosatConfirm></TosatConfirm>
  </div>
</template>

<style scoped>
.pointer {
  cursor: pointer;
}
</style>
