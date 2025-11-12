<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId } from 'vue';

import { useToast } from 'primevue/usetoast';
const toast = useToast();
import { useConfirm } from 'primevue/useconfirm';
const confirm = useConfirm();

import { setMagicIcon } from '../apiCall';

import TopMenu from './component/TopMenu.vue';
import RouteParam from './component/RouteParam.vue';
import Help from './component/Help.vue';
import EditArticle from './component/EditArticle.vue';
import FileManager from './component/FileManager.vue';

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

  // document.showToast('Сообщение');
  // document.confirmDialog('Сохранить?');

  // глобальные сервисы диалог подтверждения и тосты
  document.showToast = (msg = '', severity = 'success') => {
    const life = severity === 'success' ? 5000 : 60 * 1000;
    toast.add({ severity: severity, detail: msg, life: life });
    if (severity === 'error') {
      console.log(msg);
    }
  };
  document.confirmDialog = async (message) => {
    return new Promise((resolve, reject) => {
      confirm.require({
        message,
        header: '',
        icon: 'fas fa-question',
        acceptLabel: 'Да',
        rejectLabel: 'Нет',
        accept: () => resolve(true),
        reject: () => resolve(false), // или reject(), если хотите ошибку
      });
    });
  };
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
  <FileManager></FileManager>
  <Help></Help>
  <RouteParam v-if="store.articleReady"></RouteParam>
  <!-- тосты -->
  <Toast position="bottom-right"></Toast>
  <!-- Дилог Да Нет -->
  <ConfirmDialog></ConfirmDialog>
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
