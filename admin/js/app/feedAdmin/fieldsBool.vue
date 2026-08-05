<script setup>
import { useFeedStore } from './store.js';
import { useI18n } from 'vue-i18n';
import InputText from 'primevue/inputtext';

const { t } = useI18n();
const store = useFeedStore();

const KEY = 'bool';

async function removeRow(row) {
  if (!(await document.confirmDialog(t('feed_field_delete_ask') + ' ' + (row.code || row.column)))) return;

  try {
    await store.removeRow(KEY, row);
  } catch (error) {}
}
</script>

<template>
  <div class="mb-4">
    <h2 class="h6 mb-2">{{ t('feed_group_bool') }}</h2>

    <table class="table table-sm align-middle" style="max-width: 60rem">
      <thead>
        <tr>
          <th style="width: 12rem">
            <button class="btn btn-sm btn-success py-0 me-2" :title="t('add')" @click="store.addRow(KEY)">
              <i class="fas fa-plus small"></i>
            </button>
            Code
          </th>
          <th style="width: 12rem">Label</th>
          <th style="width: 8rem">default</th>
          <th>validation</th>
          <th style="width: 6rem"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in store.rows.bool" :key="row.column">
          <td>
            <InputText v-model="row.code" class="form-control form-control-sm" :disabled="store.itemsCount > 0" />
          </td>
          <td><InputText v-model="row.label" class="form-control form-control-sm" /></td>
          <td class="text-center"><input type="checkbox" v-model="row.default" /></td>
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

<style scoped></style>
