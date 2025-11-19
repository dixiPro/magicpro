<script setup>
import { ref, computed, onMounted, onUnmounted, watch, useId, toRaw, unref } from 'vue';
import { apiCall, apiArt, translitString } from '../apiCall';

import { useToast } from 'primevue/usetoast';
const toast = useToast();
import { useConfirm } from 'primevue/useconfirm';
const confirm = useConfirm();

onMounted(() => {
  console.log('startCrawler');

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

onUnmounted(() => {});

// результаты
const result = ref({});
// внешние ссылки
const extrnalLink = ref({});
// экстренный стоп
const stop = ref(false);
//
const status = ref('start');

async function checkUrlByPhp(url) {
  try {
    const response = await apiCall({
      url: '/a_dmin/api/setup',
      data: { command: 'processUrl', url: url },
      logResult: false,
    });
    return response.data;
  } catch (e) {
    document.showToast(e.message, 'error');
  }
}

function fixUrl(url) {
  try {
    return new URL(url).href; // абсолютный → не трогаем
  } catch {
    return new URL(url, location.origin).href; // относительный → добавим домен
  }
}

async function getPaget(url) {
  try {
    const res = await checkUrlByPhp(fixUrl(url));
    return res;
  } catch (error) {
    throw new Error(error);
  }
}

function getLinks(url) {
  if (stop.value) return;
  return new Promise(async (resolve, reject) => {
    try {
      const res = await getPaget(url);
      if (!res.check) {
        return reject({
          error: res.code,
        });
      }
      const html = res.body;
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const links = [...doc.querySelectorAll('a')].map((a) => a.getAttribute('href')).filter(Boolean);
      resolve(links);
    } catch (e) {
      reject({
        error: e.message,
      });
    }
  });
}

async function deleteCache() {
  try {
    const response = await apiCall({
      url: '/a_dmin/api/setup',
      data: { command: 'deleteCache' },
      logResult: false,
    });
    document.showToast('кеш удален');
  } catch (e) {
    document.showToast(e.message, 'error');
  }
}
async function start(url, parent) {
  status.value = 'working';
  if (stop.value) return;

  let sorted = {};
  Object.keys(result.value)
    .sort()
    .forEach((k) => {
      sorted[k] = result.value[k];
    });

  result.value = sorted;

  await checkExternal();

  sorted = {};
  Object.keys(extrnalLink.value)
    .sort()
    .forEach((k) => {
      sorted[k] = extrnalLink.value[k];
    });

  extrnalLink.value = sorted;
  status.value = 'end';
}

async function checkExternal() {
  if (stop.value) return;
  for (let key in extrnalLink.value) {
    try {
      const res = await checkUrlByPhp(key);
      extrnalLink.value[key].status = 'done';
      extrnalLink.value[key].check = res.check ? 'ok' : res.code;
    } catch (error) {}
  }
}

async function go(url, parent) {
  if (stop.value) return;
  // админка
  const arrSkip = ['#', '/a_dmin', '/f_ilament'];
  if (arrSkip.some((el) => url.startsWith(el))) {
    return;
  }

  // внешние ссылки
  const arrSkipProtocol = ['//', 'http://', 'https://'];
  if (arrSkipProtocol.some((el) => url.startsWith(el))) {
    if (!(url in extrnalLink.value)) {
      extrnalLink.value[url] = {
        status: 'reading',
        check: 'unknown',
        parentArr: [],
      };
    }
    extrnalLink.value[url].parentArr.push(parent);
    return;
  }

  // ссылку проверели
  if (url in result.value) {
    // добавляем родителей только с ошибками
    if (result.value[url].check != 'ok') {
      result.value[url].parentArr.push(parent);
    }

    return;
  }

  result.value[url] = {
    status: 'reading',
    check: 'unknown',
    parentArr: [parent],
  };
  try {
    const arr = await getLinks(url);
    // console.log('url=', url, 'arr', arr);
    result.value[url].check = 'ok';

    for (const el of arr) {
      if (stop.value) return;
      await go(el, url);
    }
  } catch (error) {
    result.value[url].check = error.error;
  } finally {
    result.value[url].status = 'done';
  }
}
</script>

<template>
  <h1>Crawler 0.71</h1>
  <div class="my-2">
    <button class="btn btn-primary" @click="deleteCache()">Удалить кэш</button>
  </div>
  <div class="my-2">
    <button v-if="status == 'start'" class="btn btn-primary fas fa-angle-right" @click="start('/', '')"></button>

    <button v-if="status == 'working'" class="btn btn-danger fas fa-stop ms-3" @click="stop = true"></button>
  </div>

  <div v-if="status != 'start'">
    <h3>Error</h3>
    <table class="table table-sm table-bordered">
      <tbody>
        <template v-for="(val, key) in result" :key="key">
          <tr v-if="val.check != 'ok'">
            <td class="link">
              <a :href="key" target="_blank">{{ key }}</a>
            </td>
            <td>{{ val.status }}</td>
            <td>{{ val.check }}</td>
            <td>
              <span v-for="parent in val.parentArr" class="me-2">
                <a :href="parent" target="_blank">{{ parent }}</a>
              </span>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <h3>Ok</h3>
    <table class="table table-sm table-bordered">
      <tbody>
        <template v-for="(val, key) in result" :key="key">
          <tr v-if="val.check == 'ok'">
            <td class="link">
              <a :href="key" target="_blank">{{ key }}</a>
            </td>
            <td>{{ val.status }}</td>
            <td>{{ val.check }}</td>
            <td>
              <span v-for="parent in val.parentArr" class="me-2">
                <a :href="parent" target="_blank">{{ parent }}</a>
              </span>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <h2>External Error</h2>
    <table class="table table-sm table-bordered">
      <tbody>
        <template v-for="(val, key) in extrnalLink" :key="key">
          <tr v-if="val.check != 'ok'">
            <td class="link">
              <a :href="key" target="_blank">{{ key }}</a>
            </td>
            <td>{{ val.status }}</td>
            <td>{{ val.check }}</td>
            <td>
              <span v-for="parent in val.parentArr" class="me-2">
                <a :href="parent" target="_blank">{{ parent }}</a>
              </span>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <h2>External OK</h2>
    <table class="table table-sm table-bordered">
      <tbody>
        <template v-for="(val, key) in extrnalLink" :key="key">
          <tr v-if="val.check == 'ok'">
            <td class="link">
              <a :href="key" target="_blank">{{ key }}</a>
            </td>
            <td>{{ val.status }}</td>
            <td>{{ val.check }}</td>
            <td>
              <span v-for="parent in val.parentArr" class="me-2">
                <a :href="parent" target="_blank">{{ parent }}</a>
              </span>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
  <!-- <pre>
      {{ JSON.stringify(extrnalLink, null, 2) }}
      </pre
  > -->

  <!-- <pre>
      {{ JSON.stringify(result, null, 2) }}
      </pre
  > -->

  <!-- тосты -->
  <Toast position="bottom-right"></Toast>
  <!-- Дилог Да Нет -->
  <ConfirmDialog></ConfirmDialog>
</template>

<style scoped>
.link {
  overflow: hidden;
  white-space: nowrap;
  max-width: 450px;
}
.pointer {
  cursor: pointer;
}
</style>
