<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch, useId, toRaw, unref } from 'vue';
import { apiSetup, apiCall } from '../apiCall';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

import { useToast } from 'primevue/usetoast';
const toast = useToast();
import { useConfirm } from 'primevue/useconfirm';
const confirm = useConfirm();

const iniParams = ref({});

const ready = ref(false);

// результаты
const result = ref({});
// внешние ссылки
const extrnalLink = ref({});
// экстренный стоп
const stop = ref(false);
// статус обхода
const status = ref('start');
// статус кеша

const storageDirStatus = ref(false);
const publicDirStatus = ref(false);

let arrSkip = []; // пропускаем сканирование

const externalProtocol = ['//', 'http://', 'https://']; // внешние ссылки

import LoadingButton from './component/LoadingButton.vue';

onMounted(() => {
  console.log('startCrawler');
  getIniParams();
});

async function getCrawlerResults() {
  function safeJsonParse(str) {
    try {
      const v = JSON.parse(str);
      return v && typeof v === 'object' ? v : {};
    } catch {
      return {};
    }
  }
  const s = await apiSetup({ command: 'getCrawlerResults' });
  const saved = safeJsonParse(s?.result);

  result.value = saved.result && typeof saved.result === 'object' ? saved.result : {};
  extrnalLink.value = saved.extrnalLink && typeof saved.extrnalLink === 'object' ? saved.extrnalLink : {};
}

async function getIniParams() {
  try {
    ready.value = false;
    // начальные параметры
    await nextTick();
    const res = await apiSetup({
      command: 'getIniParams',
    });
    iniParams.value = res;
    arrSkip = res.EXCLUDED_ROUTES;
    arrSkip.push('#');

    // состояние директорий сторадж и публик
    const dirStatus = await apiSetup({
      command: 'getDirStatus',
    });
    storageDirStatus.value = dirStatus.storageDirStatus;
    publicDirStatus.value = dirStatus.publicDirStatus;

    await getCrawlerResults();

    await nextTick();

    ready.value = true;
  } catch (error) {}
}

async function wait(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

onUnmounted(() => {});

async function saveResults() {
  const savedData = JSON.stringify(
    {
      result: toRaw(result.value),
      extrnalLink: toRaw(extrnalLink.value),
    },
    null,
    2
  );
  await apiSetup({
    command: 'saveCrawlerResults',
    savedData: savedData,
  });
}

//
async function checkUrlByPhp(url) {
  const response = await apiSetup({
    command: 'processUrl',
    url: url,
  });
  return response;
}

//
function fixUrl(url) {
  try {
    return new URL(url).href; // абсолютный → не трогаем
  } catch {
    return new URL(url, location.origin).href; // относительный → добавим домен
  }
}

//
async function getLinks(url) {
  if (stop.value) return;

  const res = await checkUrlByPhp(fixUrl(url));
  if (!res.check) {
    throw new Error(res.code);
  }
  const html = res.body;
  const doc = new DOMParser().parseFromString(html, 'text/html');
  const links = [...doc.querySelectorAll('a')].map((a) => a.getAttribute('href')).filter(Boolean);
  return links;
}

//
//
async function start() {
  if (stop.value) return;
  result.value = {};
  extrnalLink.value = {};
  status.value = 'working';

  await deleteFromStorage();

  await go('/', '');

  for (const el of iniParams.value.RENDER_URL) {
    await go(el, '');
  }

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
  // await getIniParams();
}

async function checkExternal() {
  if (stop.value) return;
  for (let key in extrnalLink.value) {
    const res = await checkUrlByPhp(key);
    extrnalLink.value[key].status = 'done';
    extrnalLink.value[key].check = res.check ? 'ok' : res.code;
  }
}

async function go(url, parent) {
  if (stop.value) return;
  // админка
  if (arrSkip.some((el) => url.startsWith(el))) {
    return;
  }

  // внешние ссылки
  if (externalProtocol.some((el) => url.startsWith(el))) {
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
    result.value[url].check = error.message;
  } finally {
    result.value[url].status = 'done';
  }
}

async function deleteFromStorage() {
  await apiSetup({
    command: 'deleteFromStorage',
  });
  document.showToast('Storage удален');
  await getIniParams();
}

async function deleteFromPublic() {
  await apiSetup({
    command: 'deleteFromPublic',
  });
  document.showToast('Public удален');
  await getIniParams();
}

async function startHtmlCache() {
  await apiSetup({
    command: 'startHtmlCache',
  });
  await getIniParams();
  document.showToast('Кеш опубликован');
}

const hasInternalError = computed(() => {
  for (const key in result.value) {
    if (result.value[key]?.check != 'ok' && result.value[key]?.check != 'reading') {
      return true;
    }
  }
  return false;
});

const hasExternalError = computed(() => {
  for (const key in extrnalLink.value) {
    if (result.value[key]?.check != 'ok' && result.value[key]?.check != 'reading') {
      return true;
    }
  }
  return false;
});
</script>

<template>
  <h1>Crawler 0.81</h1>
  <div v-if="ready">
    <div class="my-2">
      <LoadingButton :action="saveResults">Сохранить результаты</LoadingButton>
    </div>

    <div class="my-2">
      <LoadingButton :action="start">Старт</LoadingButton>
      <button v-if="status == 'working'" class="btn btn-danger fas fa-stop ms-3" @click="stop = true"></button>
    </div>

    <div v-if="storageDirStatus">
      <h4>Кеш создан</h4>

      <div class="my-2">
        <LoadingButton :action="deleteFromStorage">Удалить сторадж</LoadingButton>
      </div>

      <div class="my-2">
        <LoadingButton :action="startHtmlCache">Опубликовать сторадж</LoadingButton>
      </div>
    </div>
    <div v-if="publicDirStatus">
      <h4>Кеш опубликован</h4>
      <div class="my-2">
        <LoadingButton :action="deleteFromPublic">Удалить опубликованный кеш</LoadingButton>
      </div>
    </div>

    <div v-if="hasInternalError">
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
    </div>

    <div v-if="Object.keys(result).length > 0">
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
    </div>

    <div v-if="hasExternalError">
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
    </div>
    <div v-if="Object.keys(extrnalLink).length > 1">
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

    <pre>
      {{ JSON.stringify(iniParams, null, 2) }}
      </pre
    >

    Результаты
    <pre>
      {{ JSON.stringify(result, null, 2) }}
      </pre
    >
  </div>
  <TosatConfirm></TosatConfirm>
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
