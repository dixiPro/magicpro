<script setup>
import { ref, computed } from 'vue';
import { useFeedStore } from './store.js';
import { useI18n } from 'vue-i18n';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';

const { t } = useI18n();
const store = useFeedStore();

const KEY = 'link';

// ленты, на которые можно сослаться: своя же лента исключена — запись,
// сославшаяся сама на себя, сразу замыкает кольцо
const feeds = computed(() => store.groupFeeds.filter((feed) => feed.id !== store.feedId));

// выбор связи: строка, лента и её поля
const linkRow = ref(null);
const linkFeedId = ref(null);
const linkColumn = ref('');
const linkFields = ref([]);

async function openLink(row) {
  linkRow.value = row;
  linkFeedId.value = row.relationFeedId;
  linkColumn.value = row.relationColumn;
  linkFields.value = await store.fieldsOfFeed(row.relationFeedId);
}

async function onFeedChange() {
  linkColumn.value = '';
  linkFields.value = await store.fieldsOfFeed(linkFeedId.value);
}

async function applyLink() {
  if (!linkRow.value) return;

  linkRow.value.relationFeedId = linkFeedId.value ? Number(linkFeedId.value) : null;
  linkRow.value.relationColumn = linkColumn.value;

  await store.describeLinks();

  linkRow.value = null;
}

async function removeRow(row) {
  if (!(await document.confirmDialog(t('feed_field_delete_ask') + ' ' + (row.code || row.column)))) return;

  try {
    await store.removeRow(KEY, row);
  } catch (error) {}
}
</script>

<template>
  <div class="mb-4">
    <table class="table table-sm align-middle" style="max-width: 60rem">
      <thead>
        <tr>
          <th style="width: 12rem">
            <code>{{ t('feed_group_link') }}</code> Code
            <button class="btn btn-success py-0 px-1 ms-1 lh-1" :title="t('add')" @click="store.addRow(KEY)">
              <i class="fas fa-plus" style="font-size: 0.7rem"></i>
            </button>
          </th>
          <th style="width: 12rem">Label</th>
          <th style="width: 14rem">{{ t('feed_group_link') }}</th>
          <th style="width: 5rem">{{ t('feed_show_on_list') }}</th>
          <th>validation</th>
          <th style="width: 6rem"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in store.rows.link" :key="row.column">
          <td>
            <InputText v-model="row.code" class="form-control form-control-sm" :disabled="store.itemsCount > 0" />
          </td>
          <td><InputText v-model="row.label" class="form-control form-control-sm" /></td>
          <td>
            <button class="btn btn-sm btn-outline-primary w-100 text-truncate" @click="openLink(row)">
              {{ row.relationTitle || t('feed_link_choose') }}
            </button>
          </td>
          <td class="text-center"><input type="checkbox" v-model="row.showOnList" /></td>
          <td><InputText v-model="row.validation" class="form-control form-control-sm" /></td>
          <td class="text-muted small text-nowrap">
            <code>{{ row.column }}</code>
            <i class="fas fa-trash ms-2 text-body" role="button" :title="t('delete')" @click="removeRow(row)"></i>
          </td>
        </tr>
      </tbody>
    </table>

    <Dialog
      :visible="linkRow !== null"
      modal
      :draggable="false"
      :header="t('feed_group_link')"
      :style="{ width: '34rem' }"
      @update:visible="linkRow = null"
    >
      <div class="mb-3">
        <label class="form-label mb-0 small">{{ t('feed_link_feed') }}</label>
        <select v-model="linkFeedId" class="form-select form-select-sm" @change="onFeedChange()">
          <option :value="null">—</option>
          <option v-for="feed in feeds" :key="feed.id" :value="feed.id">{{ feed.title }}</option>
        </select>
        <div class="form-text">{{ t('feed_link_same_group') }}</div>
      </div>

      <div class="mb-3">
        <label class="form-label mb-0 small">{{ t('feed_link_field') }}</label>
        <select v-model="linkColumn" class="form-select form-select-sm" :disabled="!linkFeedId">
          <option value="">—</option>
          <option v-for="field in linkFields" :key="field.column" :value="field.column">
            {{ field.code }} — {{ field.label }}
          </option>
        </select>
      </div>

      <button class="btn btn-sm btn-primary" @click="applyLink()">{{ t('save') }}</button>
    </Dialog>
  </div>
</template>

<style scoped>
th {
  font-weight: normal;
  font-size: 0.9rem;
}
</style>
