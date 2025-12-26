<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId } from 'vue';
import { apiCall, apiArt, translitString } from '../apiCall';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const allUsers = ref([]);
const apiActive = ref(false);
const newUser = ref({
  name: 'newUser',
  email: 'barsik@barsik.com',
  password: '1234',
  role: 'user',
});
let numerUserInAllUsers = 0; // номер редактируемого пользователя в allUsers
const dialogUserForm = ref({
  show: false,
  mode: null,
  header: null,
  button: null,
});

// эту функцию использовать для обращения к АПИ
async function apiEditUser(data) {
  const url = '/a_dmin/api/editUsers';
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

async function readUsers() {
  try {
    allUsers.value = (
      await apiEditUser({
        command: 'getUserList',
        data: {},
      })
    ).map((el) => {
      el.edit = false;
      return el;
    });
    document.showToast(t('user_list_loaded'));
  } catch (error) {}
}

async function editUser() {
  if (!(await document.confirmDialog(t('save')))) return;

  try {
    const res = await apiEditUser({
      command: 'editUser',
      data: newUser.value,
    });
    res.edit = false;
    console.log(res);
    allUsers.value[numerUserInAllUsers] = res;
    newUser.value = res;
    document.showToast(t('saved'));
  } catch (error) {}
}

async function deleteUser(selectedUser) {
  if (!(await document.confirmDialog(t('delete')))) return;
  try {
    const res = await apiEditUser({
      command: 'deleteUser',
      data: { id: allUsers.value[selectedUser].id },
    });
    allUsers.value.splice(selectedUser, 1);
  } catch (error) {}
}

function startEdit(selectedUser) {
  numerUserInAllUsers = selectedUser;
  newUser.value = allUsers.value[selectedUser];
  dialogUserForm.value.show = true;
  dialogUserForm.value.header = t('edit');
  dialogUserForm.value.mode = 'edit';
  dialogUserForm.value.header = t('save');
}

function startAdd() {
  newUser.value = {
    name: 'newUser',
    email: 'barsik@barsik.com',
    password: '1234',
    role: 'user',
  };
  dialogUserForm.value.show = true;
  dialogUserForm.value.header = t('create');
  dialogUserForm.value.mode = 'add';
  dialogUserForm.value.header = t('create');
}

async function addUser() {
  try {
    const res = await apiEditUser({
      command: 'addUser',
      data: newUser.value,
    });
    res.edit = false;
    allUsers.value.push(res);
    dialogUserForm.value.show = false;
  } catch (error) {}
}

onMounted(() => {
  readUsers();
});

onUnmounted(() => {});
</script>
<template>
  <div class="container my-3">
    <template v-for="(user, index) in allUsers" :key="index">
      <div class="row my-2">
        <div class="col-1">
          <button @click="startEdit(index)" class="fas fa-edit btn btn-sm btn-success"></button>
        </div>

        <div class="col-1">
          <span v-text="user.id"></span>
        </div>
        <div class="col-2">
          <span v-text="user.name"></span>
        </div>
        <div class="col-2">
          <span v-text="user.email"></span>
        </div>
        <div class="col-2">
          <span v-text="user.password"></span>
        </div>
        <div class="col-2">
          <span v-text="user.role"></span>
        </div>

        <div class="col-1" v-if="!user.edit">
          <button class="fas fa-trash btn btn-sm btn-success" @click="deleteUser(index)"></button>
        </div>
      </div>
    </template>
    <div class="my-3 text-center">
      <button class="btn btn-sm btn-success" @click="startAdd()">{{ t('add') }}</button>
    </div>
  </div>

  <Dialog v-model:visible="dialogUserForm.show" :header="dialogUserForm.header" modal style="width: 400px">
    <div>name <input type="text" class="form-control" v-model="newUser.name" placeholder="name" /></div>
    <div class="my-2">
      <input type="text" class="form-control" v-model="newUser.email" placeholder="email" />
    </div>
    <div class="my-2"><input type="text" class="form-control" v-model="newUser.password" placeholder="password" /></div>
    <div class="my-2"><input type="text" class="form-control" v-model="newUser.role" placeholder="password" /></div>

    <button class="btn btn-sm btn-success" v-if="dialogUserForm.mode === 'add'" @click="addUser">{{ t('create') }}</button>
    <button class="btn btn-sm btn-success" v-if="dialogUserForm.mode === 'edit'" @click="editUser">{{ t('save') }}</button>
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
