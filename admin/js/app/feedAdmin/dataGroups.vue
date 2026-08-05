<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { apiFeed } from './api.js';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const router = useRouter();

const groups = ref([]);

// те же группы, что и в структуре, но без правки: здесь только выбирают, куда идти
async function load() {
  try {
    const list = await apiFeed({ command: 'groupsList' });
    const allFeeds = await apiFeed({ command: 'feedsList' });

    groups.value = list.map((group) => ({
      ...group,
      feedsCount: allFeeds.filter((feed) => feed.groupId === group.id).length,
    }));
  } catch (error) {}
}

function openGroup(group) {
  router.push({ name: 'dataFeeds', params: { groupId: group.id } });
}

onMounted(() => {
  load();
});
</script>

<template>
  <div>
    <h1 class="h4 mb-3">{{ t('feed_title') }}</h1>

    <div v-if="groups.length === 0" class="text-muted">{{ t('feed_no_groups') }}</div>

    <DataTable v-else :value="groups" size="small" tableStyle="max-width: 40rem">
      <Column :header="t('feed_group')">
        <template #body="{ data }">
          <a href="#" @click.prevent="openGroup(data)">{{ data.title }}</a>
        </template>
      </Column>
      <Column field="feedsCount" :header="t('feed_feeds_count')" headerStyle="width: 6rem" />
    </DataTable>
  </div>
</template>

<style scoped></style>
