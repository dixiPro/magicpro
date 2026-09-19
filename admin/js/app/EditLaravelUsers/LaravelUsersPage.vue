<script setup>
/**
 * Страница «Пользователи»: две вкладки — список пользователей сайта и
 * настройки регистрации и входа.
 */
import { ref } from 'vue';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';
import EditLaravelUsers from './EditLaravelUsers.vue';
import AuthSettings from './AuthSettings.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const tab = ref('users');
</script>

<template>
  <ul class="nav nav-tabs mt-3">
    <li class="nav-item">
      <a href="#" class="nav-link" :class="{ active: tab === 'users' }" @click.prevent="tab = 'users'">{{ t('site_users') }}</a>
    </li>
    <li class="nav-item">
      <a href="#" class="nav-link" :class="{ active: tab === 'settings' }" @click.prevent="tab = 'settings'">{{ t('auth_settings') }}</a>
    </li>
  </ul>

  <EditLaravelUsers v-if="tab === 'users'" />
  <AuthSettings v-else />

  <TosatConfirm></TosatConfirm>
</template>
