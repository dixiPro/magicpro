<script setup>
import { useFeedStore } from './store.js';
import { useI18n } from 'vue-i18n';
import InputText from 'primevue/inputtext';

const { t } = useI18n();
const store = useFeedStore();

const KEY = 'date';

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
            <code>{{ t('feed_group_date') }}</code> Code
            <button class="btn btn-success py-0 px-1 ms-1 lh-1" :title="t('add')" @click="store.addRow(KEY)">
              <i class="fas fa-plus" style="font-size: 0.7rem"></i>
            </button>
          </th>
          <th style="width: 12rem">Label</th>
          <th style="width: 5rem">{{ t('feed_show_on_list') }}</th>
          <th style="width: 8rem">{{ t('feed_list_format') }}</th>
          <th>validation</th>
          <th style="width: 6rem"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in store.rows.date" :key="row.column">
          <td>
            <InputText v-model="row.code" class="form-control form-control-sm" :disabled="store.itemsCount > 0" />
          </td>
          <td><InputText v-model="row.label" class="form-control form-control-sm" /></td>
          <td class="text-center"><input type="checkbox" v-model="row.showOnList" /></td>
          <td>
            <InputText
              v-model="row.listFormat"
              placeholder="y-m-d"
              :title="t('feed_list_format_help')"
              class="form-control form-control-sm"
            />
          </td>
          <td><InputText v-model="row.validation" class="form-control form-control-sm" /></td>
          <td class="text-muted small text-nowrap">
            <code>{{ row.column }}</code>
            <i class="fas fa-trash ms-2 text-body" role="button" :title="t('delete')" @click="removeRow(row)"></i>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
th {
  font-weight: normal;
  font-size: 0.9rem;
}
</style>
