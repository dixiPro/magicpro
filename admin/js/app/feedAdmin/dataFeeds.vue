<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { apiFeed } from './api.js';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
  groupId: { type: [String, Number], required: true },
});

const router = useRouter();

const groupId = computed(() => Number(props.groupId));
const groupTitle = ref('');
const feeds = ref([]);

async function load() {
  try {
    const groups = await apiFeed({ command: 'groupsList' });

    groupTitle.value = groups.find((group) => group.id === groupId.value)?.title ?? '';

    feeds.value = await apiFeed({ command: 'feedsList', groupId: groupId.value });
  } catch (error) {}
}

function openFeed(feed) {
  router.push({ name: 'dataItems', params: { feedId: feed.id } });
}

watch(groupId, () => load());

onMounted(() => {
  load();
});
</script>

<template>
  <div>
    <h1 class="h4 mb-3">
      <RouterLink :to="{ name: 'data' }" class="text-decoration-none me-2">
        <i class="fas fa-arrow-left"></i>
      </RouterLink>
      {{ groupTitle }}
    </h1>

    <div v-if="feeds.length === 0" class="text-muted">{{ t('feed_no_feeds') }}</div>

    <DataTable v-else :value="feeds" size="small" tableStyle="max-width: 46rem">
      <Column :header="t('feed_name')">
        <template #body="{ data }">
          <a href="#" @click.prevent="openFeed(data)">{{ data.title }}</a>
        </template>
      </Column>
      <Column :header="t('feed_code')">
        <template #body="{ data }">
          <code>{{ data.code }}</code>
        </template>
      </Column>
      <Column field="itemsCount" :header="t('feed_items_count')" headerStyle="width: 6rem" />
    </DataTable>
  </div>
</template>

<style scoped></style>
