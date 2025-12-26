<script setup>
import { ref, computed, isProxy, isReactive, onMounted, onUnmounted, nextTick, watch, useId, toRaw, unref } from 'vue';
import { apiSetup, apiCall } from '../apiCall';

import TosatConfirm from '../CommonCom/ToastConfirm.vue';

import EditString from './component/EditString.vue';
import EditBoolean from './component/EditBoolean.vue';
import EditArray from './component/EditArray.vue';

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const paramsAttr = ref({});
const iniParams = ref({});
const ready = ref(false);

onMounted(() => {
  getIniParams();
});

onUnmounted(() => {});

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
  document.showToast(t('Saved'));

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
  document.showToast(t('reseted'));

  await getIniParams();
}
</script>

<template>
  <div v-if="ready">
    <div class="row my-3" v-for="(value, key) in paramsAttr" :key="key">
      <div class="col-3">
        <div><strong v-text="key"></strong></div>
        <div style="line-height: 1">
          <small v-text="value.label"></small>
        </div>
      </div>
      <div class="col-md-5">
        <EditString
          v-if="value.type == 'localpath' || value.type == 'string'"
          v-model="iniParams[key]"
          :defaultValue="value.default"
          :mutable="value.mutable"
        ></EditString>
        <EditBoolean v-if="value.type == 'boolean'" v-model="iniParams[key]" :defaultValue="value.default" :mutable="value.mutable"></EditBoolean>
        <EditArray v-if="value.type == 'array'" v-model="iniParams[key]" :defaultValue="value.default" :mutable="value.mutable"></EditArray>
      </div>
    </div>
    <div class="row">
      <div class="col-md-5 text-center">
        <button class="btn btn-sm btn-success" @click="saveParams">{{ t('save') }}</button>
      </div>
      <div class="col-md-3 text-end">
        <button class="btn btn-sm btn-danger" @click="restoreParams">{{ t('reset') }}</button>
      </div>
    </div>
  </div>

  <pre>
      {{ JSON.stringify(iniParams, null, 2) }}
      </pre
  >

  paramsAttr
  <pre>
      {{ JSON.stringify(paramsAttr, null, 2) }}
      </pre
  >
  <TosatConfirm></TosatConfirm>
</template>

<style>
.pointer {
  cursor: pointer;
}
</style>
