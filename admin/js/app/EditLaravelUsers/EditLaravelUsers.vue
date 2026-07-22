<script setup>
import { ref, onMounted } from 'vue';
import { apiCall } from '../apiCall.js';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const allUsers = ref([]);
const apiActive = ref(false);
const search = ref('');

let searchTimer = null; // таймер debounce для поля search

let numerUserInAllUsers = 0; // номер редактируемого пользователя в allUsers
const editUserForm = ref({
  id: null,
  name: '',
  email: '',
  password: '',
});
const dialogUserForm = ref({
  show: false,
});

// эту функцию использовать для обращения к АПИ
async function apiLaravelUsers(data) {
  const url = '/a_dmin/api/laravelUsers';
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

// загрузить список пользователей
// emailPart = '' — последние 20 зарегистрированных
// emailPart != '' — первые 20, у которых email содержит emailPart
async function readUsers(emailPart = '') {
  try {
    const res = await apiLaravelUsers({
      command: 'getUserList',
      count: 20,
      emailPart: emailPart,
    });
    allUsers.value = res.users;
  } catch (error) {}
}

// формат даты: 2026-07-18T15:09:24.000000Z -> 2026-07-18 15:09:24
function formatDate(value) {
  if (!value) return '';
  const m = String(value).match(/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2}:\d{2})/);
  return m ? `${m[1]} ${m[2]}` : value;
}

// ввод в поле search: автоматический поиск с debounce
function onSearch() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    readUsers(search.value.trim());
  }, 300);
}

// клик по юзеру: открыть окно со всеми полями
// password пустой — при сохранении пароль не меняется
function startEdit(index) {
  numerUserInAllUsers = index;
  const user = allUsers.value[index];
  editUserForm.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    password: '',
  };
  dialogUserForm.value.show = true;
}

// сохранить изменения пользователя
async function editUser() {
  try {
    const res = await apiLaravelUsers({
      command: 'editUser',
      id: editUserForm.value.id,
      name: editUserForm.value.name,
      email: editUserForm.value.email,
      password: editUserForm.value.password,
    });
    // обновить строку в списке
    allUsers.value[numerUserInAllUsers] = {
      ...allUsers.value[numerUserInAllUsers],
      name: res.name,
      email: res.email,
    };
    dialogUserForm.value.show = false;
    document.showToast(t('saved'));
  } catch (error) {}
}

// войти под этим пользователем (авторизация по id)
async function loginAsUser(index) {
  const user = allUsers.value[index];
  try {
    await apiLaravelUsers({
      command: 'authById',
      id: user.id,
    });
    document.showToast(t('logged_in_as') + ' ' + user.email);
  } catch (error) {}
}

onMounted(() => {
  readUsers();
});
</script>

<template>
  <div class="container my-3">
    <div class="row my-3">
      <div class="col-6">
        <input
          type="text"
          class="form-control"
          v-model="search"
          @input="onSearch"
          :placeholder="t('search')"
        />
      </div>
    </div>

    <template v-for="(user, index) in allUsers" :key="index">
      <div class="row my-2 pointer" @click="startEdit(index)">
        <div class="col-1">
          <span v-text="user.id"></span>
        </div>
        <div class="col-3">
          <span v-text="user.name"></span>
        </div>
        <div class="col-4">
          <span v-text="user.email"></span>
        </div>
        <div class="col-3">
          <span v-text="formatDate(user.created_at)"></span>
        </div>
        <div class="col-1">
          <i
            class="fas fa-sign-in-alt pointer"
            :title="t('login_as_user')"
            @click.stop="loginAsUser(index)"
          ></i>
        </div>
      </div>
    </template>
  </div>

  <Dialog v-model:visible="dialogUserForm.show" :header="t('edit')" modal style="width: 400px">
    <div class="my-2">
      id <input type="text" class="form-control" :value="editUserForm.id" disabled />
    </div>
    <div class="my-2">
      <input type="text" class="form-control" v-model="editUserForm.name" placeholder="name" />
    </div>
    <div class="my-2">
      <input type="text" class="form-control" v-model="editUserForm.email" placeholder="email" />
    </div>
    <div class="my-2">
      <input type="text" class="form-control" v-model="editUserForm.password" placeholder="password" />
    </div>

    <button class="btn btn-sm btn-success" @click="editUser">{{ t('save') }}</button>
  </Dialog>

  <TosatConfirm></TosatConfirm>
</template>

<style>
.pointer {
  cursor: pointer;
}

.icon-menue {
  cursor: pointer;
  font-size: 28px;
}

.icon-border {
  border: 1px solid #777;
}

/* 🔧 Компактный режим для PrimeVue */
:root {
  --p-input-padding: 0.25rem 0.5rem;
  --p-button-padding: 0.25rem 0.75rem;
  --p-inputtext-font-size: 0.875rem;
  --p-button-font-size: 0.875rem;
  --p-border-radius: 0.25rem;
  --p-dialog-padding: 1rem;
  --p-dialog-header-padding: 0.5rem;
  --p-dialog-content-padding: 0.5rem;
  --p-dialog-footer-padding: 0.5rem;
}

/* Немного ужимаем таблицы и карточки */
.p-datatable,
.p-card {
  font-size: 0.875rem;
}

.p-inputtext,
.p-dropdown,
.p-multiselect,
.p-calendar,
.p-password {
  min-height: 1.75rem !important;
  line-height: 1.25rem !important;
}

/* Кнопки */
.p-button {
  min-height: 1.75rem !important;
  line-height: 1.25rem !important;
}

/* Диалоги */
.p-dialog {
  font-size: 0.875rem;
}
</style>
