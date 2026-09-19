<script setup>
/**
 * Настройки регистрации и входа на сайте — группа AUTH из схемы.
 *
 * Хранятся в общем файле настроек, а он пишется только целиком: параметр,
 * которого нет в запросе, вернётся к умолчанию. Поэтому читаем все настройки,
 * правим одну группу и сохраняем все.
 */
import { ref, onMounted } from 'vue';
import { apiSetup } from '../apiCall';
import EditGroup from '../Setup/component/EditGroup.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const ready = ref(false);
const fields = ref({});
const iniParams = ref({});

async function load() {
  const attr = await apiSetup({ command: 'getParamsAttr' });
  fields.value = attr.AUTH?.data ?? {};
  const ini = await apiSetup({ command: 'getIniParams' });
  // группы ещё нет в файле настроек: без объекта заранее поля пишут мимо данных
  ini.AUTH = { ...(ini.AUTH ?? {}) };
  iniParams.value = ini;
  ready.value = true;
}

async function save() {
  if (!(await document.confirmDialog(t('save')))) {
    return;
  }
  iniParams.value = await apiSetup({
    command: 'saveIniParams',
    allVars: iniParams.value,
  });
  document.showToast(t('setup_saved'));
}

onMounted(() => {
  load();
});
</script>

<template>
  <div v-if="ready" class="col-md-6 my-3">
    <EditGroup v-model="iniParams.AUTH" :fields="fields" />
    <button class="btn btn-sm btn-success" @click="save">{{ t('save') }}</button>
  </div>
</template>
