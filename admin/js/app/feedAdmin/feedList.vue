<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { apiFeed } from './api.js';
import { setCutFeed } from './cutBuffer.js';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ContextMenu from 'primevue/contextmenu';
import InputText from 'primevue/inputtext';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
  groupId: { type: [String, Number], required: true },
});

const router = useRouter();

const groupId = computed(() => Number(props.groupId));
const groupTitle = ref('');
const feeds = ref([]);

const menu = ref(null);
const menuItems = ref([]);

// правка названия и кода прямо в списке
const editingId = ref(null);
const editingTitle = ref('');
const editingCode = ref('');

function startEdit(feed) {
  editingId.value = feed.id;
  editingTitle.value = feed.title;
  editingCode.value = feed.code;
  nextTick(() => document.querySelector('.feed-title-input')?.select());
}

function cancelEdit() {
  editingId.value = null;
}

/**
 * Правятся два поля сразу, поэтому по потере фокуса не сохраняем: переход из
 * названия в код — это тоже потеря фокуса. Enter сохраняет, Esc отменяет.
 */
async function saveEdit(feed) {
  if (editingId.value !== feed.id) return;

  const title = editingTitle.value.trim();
  const code = editingCode.value.trim();

  if (title === feed.title && code === feed.code) {
    editingId.value = null;

    return;
  }

  try {
    await apiFeed({ command: 'feedSave', id: feed.id, title: title, code: code });
    editingId.value = null;
    await load();
  } catch (error) {}
}

async function load() {
  try {
    const groups = await apiFeed({ command: 'groupsList' });

    groupTitle.value = groups.find((group) => group.id === groupId.value)?.title ?? '';

    feeds.value = await apiFeed({ command: 'feedsList', groupId: groupId.value });
  } catch (error) {}
}

function openFeed(feed) {
  router.push({ name: 'feed', params: { feedId: feed.id } });
}

// пустая лента в конец группы. Код сгенерированный, название временное —
// и то и другое оператор меняет на экране ленты
async function createFeed() {
  try {
    await apiFeed({ command: 'feedCreate', groupId: groupId.value });
    await load();
  } catch (error) {}
}

async function onFeedReorder(event) {
  const feed = event.value[event.dropIndex];
  feeds.value = event.value;

  try {
    await apiFeed({
      command: 'feedMove',
      id: feed.id,
      groupId: groupId.value,
      position: event.dropIndex,
    });
    await load();
  } catch (error) {}
}

// правая кнопка: вырезать. Вставляют на экране групп
function onFeedContext(event) {
  const feed = event.data;

  menuItems.value = [
    {
      label: t('cut'),
      icon: 'fas fa-cut',
      command: () => {
        setCutFeed(feed);
        document.showToast(t('feed_cut_done') + ' ' + feed.title);
      },
    },
  ];

  menu.value.show(event.originalEvent);
}

watch(groupId, () => load());

onMounted(() => {
  load();
});
</script>

<template>
  <div>
    <div class="d-flex align-items-center mb-3">
      <h1 class="h4 mb-0 me-3">
        <RouterLink :to="{ name: 'groups' }" class="text-decoration-none me-2"> <i class="fas fa-arrow-left"></i> </RouterLink>
        {{ groupTitle }}
      </h1>

      <button class="btn btn-sm btn-success" @click="createFeed()"><i class="fas fa-plus me-1"></i>{{ t('feed_name') }}</button>
    </div>

    <div v-if="feeds.length === 0" class="text-muted">{{ t('feed_no_feeds') }}</div>

    <DataTable v-else :value="feeds" @row-reorder="onFeedReorder" @row-contextmenu="onFeedContext" size="small" tableStyle="max-width: 50rem">
      <Column rowReorder headerStyle="width: 3rem" />
      <Column :header="t('feed_name')">
        <template #body="{ data }">
          <InputText
            v-if="editingId === data.id"
            v-model="editingTitle"
            class="feed-title-input form-control form-control-sm"
            autofocus
            @keyup.enter="saveEdit(data)"
            @keyup.esc="cancelEdit()"
          />
          <a v-else href="#" @click.prevent="openFeed(data)">{{ data.title }}</a>
        </template>
      </Column>
      <Column :header="t('feed_code')">
        <template #body="{ data }">
          <InputText
            v-if="editingId === data.id"
            v-model="editingCode"
            class="form-control form-control-sm"
            @keyup.enter="saveEdit(data)"
            @keyup.esc="cancelEdit()"
          />
          <code v-else>{{ data.code }}</code>
        </template>
      </Column>
      <Column field="itemsCount" :header="t('feed_items_count')" headerStyle="width: 6rem" />
      <Column headerStyle="width: 6rem">
        <template #body="{ data }">
          <template v-if="editingId === data.id">
            <i class="fas fa-check me-3" role="button" :title="t('save')" @click="saveEdit(data)"></i>
            <i class="fas fa-times" role="button" :title="t('cancel')" @click="cancelEdit()"></i>
          </template>
          <i v-else class="fas fa-pen" role="button" :title="t('rename')" @click="startEdit(data)"></i>
        </template>
      </Column>
    </DataTable>

    <ContextMenu ref="menu" :model="menuItems" />
  </div>
</template>

<style scoped></style>
