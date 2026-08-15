<script setup>
// Оболочка админки лент: два раздела и общее — тосты с диалогом подтверждения.
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const route = useRoute();

// Открыта лента — обе кнопки остаются в ней: со схемы идут смотреть записи, из
// записей — править схему, а не искать ленту заново в общем списке. feedId есть
// у экранов ленты и у списка её записей, у остальных его нет.
const feedId = computed(() => route.params.feedId ?? null);

const dataTarget = computed(() =>
  feedId.value ? { name: 'dataItems', params: { feedId: feedId.value } } : { name: 'data' },
);

const structureTarget = computed(() =>
  feedId.value ? { name: 'feed', params: { feedId: feedId.value } } : { name: 'groups' },
);
</script>

<template>
  <!--
    Не container: он фиксированной ширины и центрируется, а места в таблицах и
    так впритык. Отступы по бокам даёт шаблон админки.
  -->
  <div class="my-3">
    <div class="btn-group btn-group-sm mb-4" role="group">
      <RouterLink :to="structureTarget" class="btn" :class="String(route.name).startsWith('data') ? 'btn-outline-primary' : 'btn-primary'">
        {{ t('feed_tab_structure') }}
      </RouterLink>
      <RouterLink :to="dataTarget" class="btn" :class="String(route.name).startsWith('data') ? 'btn-primary' : 'btn-outline-primary'">
        {{ t('feed_tab_data') }}
      </RouterLink>
    </div>

    <RouterView />

    <TosatConfirm></TosatConfirm>
  </div>
</template>

<style scoped></style>
