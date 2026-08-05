<script setup>
// Оболочка админки лент: два раздела и общее — тосты с диалогом подтверждения.
import { useRoute } from 'vue-router';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const route = useRoute();
</script>

<template>
  <div class="container my-3">
    <div class="btn-group btn-group-sm mb-4" role="group">
      <RouterLink :to="{ name: 'groups' }" class="btn" :class="String(route.name).startsWith('data') ? 'btn-outline-primary' : 'btn-primary'">
        {{ t('feed_tab_structure') }}
      </RouterLink>
      <RouterLink :to="{ name: 'data' }" class="btn" :class="String(route.name).startsWith('data') ? 'btn-primary' : 'btn-outline-primary'">
        {{ t('feed_tab_data') }}
      </RouterLink>
    </div>

    <RouterView />

    <TosatConfirm></TosatConfirm>
  </div>
</template>

<style scoped></style>
