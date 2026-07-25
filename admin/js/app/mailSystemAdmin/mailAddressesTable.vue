<script setup>
import { ref, computed, onMounted } from 'vue';
import { apiCall } from '../apiCall.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const COUNT = 30;

const search = ref('');
const addresses = ref([]);
const total = ref(0);
const offset = ref(0);
const apiActive = ref(false);

let searchTimer = null; // таймер debounce для поля search

// есть ли ещё адреса для подгрузки
const canLoadMore = computed(() => addresses.value.length < total.value);

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

// загрузить список адресов
// reset = true — начать список заново, false — подгрузить следующую порцию
async function loadList(reset = true) {
  if (reset) {
    offset.value = 0;
    addresses.value = [];
  }
  try {
    const res = await apiMail({
      command: 'addressesList',
      search: search.value.trim(),
      count: COUNT,
      offset: offset.value,
    });
    addresses.value.push(...res.addresses);
    total.value = res.total;
    offset.value = addresses.value.length;
  } catch (error) {}
}

// ввод в поле search: автоматический поиск с debounce
function onSearch() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    loadList(true);
  }, 300);
}

onMounted(() => {
  loadList(true);
});
</script>

<template>
  <div>
    <div class="d-flex align-items-start gap-2">
      <div class="flex-grow-1 my-3">
        <input v-model="search" type="text" class="form-control form-control-sm" :placeholder="t('search')" @input="onSearch" />
      </div>
    </div>

    <!-- список -->
    <div class="table-responsive">
      <table class="table table-sm align-top small">
        <thead>
          <tr>
            <th>{{ t('mail_address_email') }}</th>
            <th>{{ t('mail_address_blocked') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="address in addresses" :key="address.id">
            <td v-text="address.email"></td>
            <td>
              <i v-if="address.blocked" class="fas fa-ban text-danger" :title="address.block_reason"></i>
            </td>
          </tr>
          <!--  -->
          <tr v-if="!addresses.length">
            <td colspan="2" class="text-center text-muted">
              {{ t('mail_empty') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- подгрузка -->
    <div class="my-3 text-center" v-if="canLoadMore">
      <button class="btn btn-sm btn-outline-secondary" @click="loadList(false)">{{ t('mail_load_more') }} ({{ addresses.length }} / {{ total }})</button>
    </div>
  </div>
</template>
