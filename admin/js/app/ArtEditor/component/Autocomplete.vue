<script setup>
import { ref, computed, onMounted, onUnmounted, watch, reactive, nextTick, toRaw } from 'vue';
import { useArticleStore } from '../store';
const store = useArticleStore();

import { useI18n } from 'vue-i18n';
const { t } = useI18n();
</script>

<template>
  <Drawer v-model:visible="store.statusAutocompletePannel" :header="t('autocomplete_controller')" position="bottom" style="width: 700px; height: 50%">
    <div class="">
      <div class="">
        <button class="btn btn-success btn-sm" @click="store.getController()">{{ t('autocomplete_simple_controller') }}</button>
      </div>
      <div class="my-2">
        <button class="btn btn-success btn-sm" @click="store.getLiveWareController()">{{ t('autocomplete_liveWare_controller') }}</button>
      </div>
    </div>
  </Drawer>
</template>
<style>
.p-drawer-header {
  padding-bottom: 0 !important;
  padding-top: 10px;
}
</style>
