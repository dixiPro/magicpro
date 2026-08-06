<script setup>
import { computed, onMounted, watch } from 'vue';
import { useFeedStore } from './store.js';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
  feedId: { type: [String, Number], required: true },
});

const store = useFeedStore();

// экраны ленты: сама вкладка живёт в адресе, компонент подставляет RouterView.
// Последний ведёт к записям, поэтому он не вкладка, а обычная ссылка
const tabs = [
  { name: 'feed', icon: 'fa-cog', title: () => t('feed_tab_structure') },
  { name: 'feedList', icon: 'fa-list', title: () => 'List' },
  { name: 'feedBuilder', icon: 'fa-wrench', title: () => 'Builder' },
  { name: 'dataItems', icon: 'fa-database', title: () => t('feed_tab_data') },
];

const feedId = computed(() => Number(props.feedId));

// ленту читаем здесь, обе вкладки берут её из стора
watch(feedId, () => store.load(feedId.value));

onMounted(() => {
  store.load(feedId.value);
});
</script>

<template>
  <div>
    <h1 class="h4 mb-3">
      <RouterLink :to="store.groupId ? { name: 'feeds', params: { groupId: store.groupId } } : { name: 'groups' }" class="text-decoration-none me-2">
        <i class="fas fa-arrow-left"></i>
      </RouterLink>
      <code>{{ store.code }}</code> {{ store.title }}

      <span class="ms-3 fs-6 fw-normal">
        <RouterLink
          v-for="tab in tabs"
          :key="tab.name"
          :to="{ name: tab.name, params: { feedId: feedId } }"
          class="me-3 text-decoration-none"
          exact-active-class="text-body"
          :title="tab.title()"
        >
          <i class="fas" :class="tab.icon"></i>
        </RouterLink>
      </span>
    </h1>

    <RouterView />
  </div>
</template>

<style scoped></style>
