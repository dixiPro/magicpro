<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId } from 'vue';
import { setMagicIcon } from '../apiCall';

import TopMenu from './component/TopMenu.vue';
import RouteParam from './component/RouteParam.vue';
import Help from './component/Help.vue';
import EditArticle from './component/EditArticle.vue';
import FileManager from '../CommonCom/FileManager.vue';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';
import Autocomplete from './component/Autocomplete.vue';

import { useArticleStore } from './store';
const store = useArticleStore();

const articleId = ref(1);

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  const obj = Object.fromEntries(params.entries());
  store.loadRec(Number(location.hash.slice(1)) || 1);

  history.pushState(articleId.value, null, '#' + articleId.value);
  console.log('--start ediror--');
  setMagicIcon('#ff642f');

  window.addEventListener('popstate', onPopState);
});

onUnmounted(() => {
  window.removeEventListener('popstate', onPopState);
});

const onPopState = (event) => {
  // event.state — это то, что передавали первым аргументом в pushState
  // location.hash — текущий хеш в адресе
  articleId.value = Number(location.hash.slice(1)) || 1;
};
</script>

<template>
  <TopMenu v-if="store.articleReady"></TopMenu>
  <EditArticle v-if="store.articleReady"></EditArticle>
  <FileManager v-model:visible="store.statusFileManager"></FileManager>
  <Help></Help>
  <RouteParam v-if="store.articleReady"></RouteParam>
  <!-- тосты -->
  <TosatConfirm></TosatConfirm>
  <Autocomplete></Autocomplete>
</template>

<style>
.pointer {
  cursor: pointer;
}

.icon-menue {
  cursor: pointer;
  font-size: 28px;
}

.icon-border {
  border: 1px solid #777;
}
:root {
  --p-dialog-header-padding: 0.2rem 1rem;
  /* новое значение */
}

.fas,
.far {
  cursor: pointer;
}

.small {
  font-size: 0.8em;
}
.tree-pannel {
  overflow-y: auto;
  height: 95%;
  overflow-x: hidden;
  white-space: nowrap;
}
</style>
