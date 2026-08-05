<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { apiFeed } from './api.js';
import { getCutFeed, clearCutFeed } from './cutBuffer.js';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ContextMenu from 'primevue/contextmenu';
import InputText from 'primevue/inputtext';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const router = useRouter();

const groups = ref([]); // группы со счётчиком лент

const editingId = ref(null); // группа, название которой правят
const editingTitle = ref('');

const menu = ref(null);
const menuItems = ref([]);

// группы: к каждой добавляем число её лент
async function loadGroups() {
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
  router.push({ name: 'feeds', params: { groupId: group.id } });
}

// новая группа в конец списка, сразу с полем ввода названия
async function createGroup() {
  try {
    const group = await apiFeed({ command: 'groupCreate' });
    await loadGroups();
    startEdit(group);
  } catch (error) {}
}

function startEdit(group) {
  editingId.value = group.id;
  editingTitle.value = group.title;
  nextTick(() => document.querySelector('.group-title-input')?.select());
}

function cancelEdit() {
  editingId.value = null;
}

// Enter или потеря фокуса. После Esc editingId уже пуст, и сохранения не будет
async function saveTitle(group) {
  if (editingId.value !== group.id) return;

  const title = editingTitle.value.trim();

  editingId.value = null;

  if (title === '' || title === group.title) {
    return;
  }

  try {
    await apiFeed({ command: 'groupSave', id: group.id, title: title });
    await loadGroups();
  } catch (error) {}
}

async function removeGroup(group) {
  if (!(await document.confirmDialog(t('feed_group_delete_ask') + ' ' + group.title))) return;

  try {
    await apiFeed({ command: 'groupDelete', id: group.id });
    await loadGroups();
  } catch (error) {}
}

// dropIndex и есть новая позиция, нумерация с нуля
async function onGroupReorder(event) {
  const group = event.value[event.dropIndex];
  groups.value = event.value;

  try {
    await apiFeed({ command: 'groupMove', id: group.id, position: event.dropIndex });
    await loadGroups();
  } catch (error) {}
}

// правая кнопка: вставить, если в буфере лента из другой группы
function onGroupContext(event) {
  const group = event.data;
  const cutFeed = getCutFeed();

  menuItems.value = [
    ...(cutFeed && cutFeed.groupId !== group.id
      ? [
          {
            label: t('insert') + ': ' + cutFeed.title,
            icon: 'fas fa-paste',
            command: () => pasteFeed(group),
          },
        ]
      : []),
    {
      label: t('rename'),
      icon: 'fas fa-pen',
      command: () => startEdit(group),
    },
    {
      label: t('delete'),
      icon: 'fas fa-trash',
      command: () => removeGroup(group),
    },
  ];

  menu.value.show(event.originalEvent);
}

// лента встаёт в конец группы
async function pasteFeed(group) {
  const cutFeed = getCutFeed();

  if (!cutFeed) return;

  try {
    await apiFeed({
      command: 'feedMove',
      id: cutFeed.id,
      groupId: group.id,
      position: group.feedsCount,
    });

    clearCutFeed();
    await loadGroups();
    document.showToast(t('feed_paste_done'));
  } catch (error) {}
}

onMounted(() => {
  loadGroups();
});
</script>

<template>
  <div>
    <div class="d-flex align-items-center mb-3">
      <h1 class="h4 mb-0 me-3">{{ t('feed_title') }}</h1>
      <button class="btn btn-sm btn-success" @click="createGroup()">
        <i class="fas fa-plus me-1"></i>{{ t('feed_group') }}
      </button>
    </div>

    <div v-if="groups.length === 0" class="text-muted">{{ t('feed_no_groups') }}</div>

    <DataTable
      v-else
      :value="groups"
      @row-reorder="onGroupReorder"
      @row-contextmenu="onGroupContext"
      size="small"
      tableStyle="max-width: 44rem"
    >
      <Column rowReorder headerStyle="width: 3rem" />
      <Column :header="t('feed_group')">
        <template #body="{ data }">
          <InputText
            v-if="editingId === data.id"
            v-model="editingTitle"
            class="group-title-input form-control form-control-sm"
            autofocus
            @keyup.enter="saveTitle(data)"
            @keyup.esc="cancelEdit()"
            @blur="saveTitle(data)"
          />
          <a v-else href="#" @click.prevent="openGroup(data)">{{ data.title }}</a>
        </template>
      </Column>
      <Column field="feedsCount" :header="t('feed_feeds_count')" headerStyle="width: 6rem" />
      <Column headerStyle="width: 6rem">
        <template #body="{ data }">
          <i class="fas fa-pen me-3" role="button" :title="t('rename')" @click="startEdit(data)"></i>
          <i class="fas fa-trash" role="button" :title="t('delete')" @click="removeGroup(data)"></i>
        </template>
      </Column>
    </DataTable>

    <ContextMenu ref="menu" :model="menuItems" />
  </div>
</template>

<style scoped></style>
