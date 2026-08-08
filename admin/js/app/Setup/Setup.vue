<script setup>
import { ref, computed, isProxy, isReactive, onMounted, onUnmounted, nextTick, watch, useId, toRaw, unref } from 'vue';
import { apiSetup, apiCall } from '../apiCall';

import TosatConfirm from '../CommonCom/ToastConfirm.vue';

import EditString from './component/EditString.vue';
import EditBoolean from './component/EditBoolean.vue';
import EditArray from './component/EditArray.vue';
import EditList from './component/EditList.vue';
import EditInteger from './component/EditInteger.vue';
import EditGroup from './component/EditGroup.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * Списки длинные, а правятся редко, поэтому их правая колонка по умолчанию
 * свёрнута: нажатие на имя параметра её показывает.
 *
 * Свёрнутыми идут массивы — это видно по типу из схемы, поэтому список имён тут
 * не нужен: новый параметр-массив ведёт себя так же сам.
 *
 * Прячем через v-show, а не v-if: свёрнутый список не должен терять то, что в
 * нём уже наредактировали.
 */
const opened = ref({});

// сворачиваем длинное: списки и группы
const collapsible = (value) => value.type === 'array' || value.type === 'group';

const isOpen = (key, value) => !collapsible(value) || opened.value[key] === true;

function toggle(key, value) {
  if (collapsible(value)) opened.value[key] = !isOpen(key, value);
}

/**
 * Параметры блоками: подряд идущие поля одной группы собираются вместе и
 * получают общий заголовок. Данные при этом плоские — группа живёт только в
 * описании и только ради экрана.
 */
const blocks = computed(() => {
  const list = [];

  for (const [key, value] of Object.entries(paramsAttr.value)) {
    const group = value.group ?? null;
    const last = list[list.length - 1];

    if (last && last.group === group) {
      last.items.push([key, value]);

      continue;
    }

    list.push({ group: group, title: value.groupLabel ?? '', items: [[key, value]] });
  }

  return list;
});

const paramsAttr = ref({});
const iniParams = ref({});
const ready = ref(false);

onMounted(() => {
  getIniParams();
});

onUnmounted(() => { });

async function getIniParams() {
  paramsAttr.value = await apiSetup({
    command: 'getParamsAttr',
  });

  iniParams.value = await apiSetup({
    command: 'getIniParams',
  });
  await nextTick();
  ready.value = true;
}

async function saveParams() {
  if (!(await document.confirmDialog(t('save')))) {
    return;
  }
  iniParams.value = await apiSetup({
    command: 'saveIniParams',
    allVars: iniParams.value,
  });
  document.showToast(t('setup_saved'));

  await getIniParams();
}

async function restoreParams() {
  if (!(await document.confirmDialog(t('reset')))) {
    return;
  }
  iniParams.value = await apiSetup({
    command: 'restoreParams',
    allVars: iniParams.value,
  });
  document.showToast(t('setup_reset'));

  await getIniParams();
}
</script>

<template>
  <div v-if="ready">
    <template v-for="block in blocks" :key="block.group ?? block.items[0][0]">
      <div v-if="block.group" class="mt-4 mb-1"><strong>{{ block.title }}</strong></div>

      <div class="row my-3" v-for="[key, value] in block.items" :key="key">
      <div class="col-3" :class="{ pointer: collapsible(value) }" @click="toggle(key, value)">
        <div>
          <i
            v-if="collapsible(value)"
            class="fas fa-chevron-right small me-1"
            :class="{ 'fa-rotate-90': isOpen(key, value) }"
          ></i>
          <strong v-text="key"></strong>
        </div>
        <div style="line-height: 1">
          <small v-text="value.label"></small>
        </div>
      </div>
      <div class="col-md-5" v-show="isOpen(key, value)">
        <EditString v-if="value.type == 'localpath' || value.type == 'string'" v-model="iniParams[key]"
          :defaultValue="value.default" :mutable="value.mutable"></EditString>
        <EditBoolean v-if="value.type == 'boolean'" v-model="iniParams[key]" :defaultValue="value.default"
          :mutable="value.mutable"></EditBoolean>
        <EditArray v-if="value.type == 'array'" v-model="iniParams[key]" :defaultValue="value.default"
          :mutable="value.mutable"></EditArray>
        <EditList v-if="value.type == 'list'" v-model="iniParams[key]" :values="value.values ?? []"
          :mutable="value.mutable"></EditList>
        <EditInteger v-if="value.type == 'integer'" v-model="iniParams[key]" :min="value.min ?? null"
          :max="value.max ?? null" :mutable="value.mutable"></EditInteger>
        <EditGroup v-if="value.type == 'group'" v-model="iniParams[key]" :fields="value.data ?? {}"></EditGroup>
        </div>
      </div>
    </template>
    <div class="row">
      <div class="col-md-5 text-center">
        <button class="btn btn-sm btn-success" @click="saveParams">{{ t('save') }}</button>
      </div>
      <div class="col-md-3 text-end">
        <button class="btn btn-sm btn-danger" @click="restoreParams">{{ t('reset') }}</button>
      </div>
    </div>
  </div>

  <!-- <pre>
      {{ JSON.stringify(iniParams, null, 2) }}
      </pre
  >

  paramsAttr
  <pre>
      {{ JSON.stringify(paramsAttr, null, 2) }}
      </pre
  > -->
  <TosatConfirm></TosatConfirm>
</template>

<style>
.pointer {
  cursor: pointer;
}
</style>
