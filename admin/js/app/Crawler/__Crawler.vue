<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch, useId, toRaw, unref } from 'vue';
import { apiSetup, apiCall } from '../apiCall';
import TosatConfirm from '../CommonCom/ToastConfirm.vue';

let totalRequest = 0;

// стартовые параметры
let iniParams = ref({});

const ready = ref(false);

// результаты
const result = ref({});

// экстренный стоп
const stop = ref(false);

// статус обхода
const status = ref('start');

// Количество обнаруженных ссылок
const statistics = ref({
  total: 0,
  error: 0,
  saveStatus: 0,
  nowReading: '',
});

function cleatStatistic() {
  statistics.value.total = 0;
  statistics.value.error = 0;
  statistics.value.saveStatus = 0;
  statistics.value.nowReading = 0;
}

// статус кеша
const storageDirStatus = ref(false);
const publicDirStatus = ref(false);

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
    } catch (e) {
      // document.showToast('JSON parse error. Ошибка в сохраненных результатах', 'error');
      return {};
    }
  }
  const s = await apiSetup({ command: 'getCrawlerResults' });
  result.value = safeJsonParse(s.result);

  cleatStatistic();
  for (const key in result.value) {
    statistics.value.total++;
    const obj = toRaw(result.value)[key];
    if (obj.saveStatus) {
      statistics.value.saveStatus++;
    }
    if (obj.check != 'ok') {
      statistics.value.error++;
    }
  }
}

async function getIniParams() {
  try {
    ready.value = false;
    // начальные параметры
    // добавить EXCLUDED_ROUTES домен
    // добавить RENDER_URL
    await nextTick();
    iniParams.value = await apiSetup({
      command: 'getIniParams',
    });

    iniParams.value.EXCLUDED_ROUTES = iniParams.value.EXCLUDED_ROUTES.map((el) => {
      let u = fixUrl(el);
      u = u.replace(/\/+$/, '');
      return u;
    });
    iniParams.value.RENDER_URL = iniParams.value.RENDER_URL.map((el) => {
      let u = fixUrl(el);
      u = u.replace(/\/+$/, '');
      return u;
    });

    // состояние директорий сторадж и публик
    const dirStatus = await apiSetup({
      command: 'getDirStatus',
    });
    storageDirStatus.value = dirStatus.storageDirStatus;
    publicDirStatus.value = dirStatus.publicDirStatus;

    await getCrawlerResults();

    await nextTick();

    ready.value = true;
  } catch (error) {
    console.log(error);
  }
}

async function wait(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function saveResults() {
  const savedData = JSON.stringify(toRaw(result.value), null, 2);
  await apiSetup({
    command: 'saveCrawlerResults',
    savedData: savedData,
  });
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
function getLinks(html) {
  const doc = new DOMParser().parseFromString(html, 'text/html');
  const links = [...doc.querySelectorAll('a')].map((a) => a.getAttribute('href')).filter(Boolean);
  return links;
}

//
//
async function start() {
  if (stop.value) return;
  result.value = {};
  status.value = 'working';
  cleatStatistic();

  await deleteFromStorage();

  for (const el of iniParams.value.RENDER_URL) {
    await go(el, '');
  }

  // await go('/', '');

  let sorted = {};
  Object.keys(result.value)
    .sort()
    .forEach((k) => {
      sorted[k] = result.value[k];
    });

  result.value = sorted;

  await saveResults();

  status.value = 'start';

  await getIniParams();
}

//
async function go(url, parent) {
  console.log(totalRequest);
  // while (totalRequest > 10) {
  //   await wait(100);
  // }

  statistics.value.nowReading = url;

  if (stop.value) return;

  url = url.replace(/#([^?]*)/g, ''); // выбросить якорь из урл

  url = fixUrl(url); // добавить http

  // исключенные адреса
  if (iniParams.value.EXCLUDED_ROUTES.some((el) => url.startsWith(el))) {
    return;
  }

  // ссылку проверели
  if (url in result.value) {
    // добавляем родителей только с ошибками иначе например на заглавную будет столько ссылок
    if (result.value[url].check != 'ok') {
      result.value[url].parentArr.push(parent);
    }
    return;
  }

  result.value[url] = {
    status: 'reading',
    check: 'unknown',
    saveStatus: false,
    parentArr: [parent],
  };

  // сохраняем, или не файл или файл из RENDER_URL
  const path = new URL(url);

  const saveToFile =
    url.startsWith(location.origin) && // текущий домен
    (iniParams.value.RENDER_URL.some((el) => url.startsWith(el)) || // доп страницы для рендера
      !path.pathname.includes('.')); // или нет точки

  try {
    // проверка и сохранение урла
    // добавить проверку на файл
    totalRequest++;
    const res = await apiSetup({
      command: 'processUrl',
      url: url,
      saveToFile: saveToFile,
    });

    if (!res.check) {
      throw new Error(res.code);
    }
    statistics.value.total++;

    result.value[url].check = 'ok';
    result.value[url].saveStatus = res.saveStatus;
    if (res.saveStatus) {
      statistics.value.saveStatus++;
    }

    // проверка на внешние ссылки
    if (!url.startsWith(location.origin)) {
      return;
    }
    const arr = getLinks(res.body);

    for (const el of arr) {
      if (stop.value) return;
      await go(el, url);
    }
  } catch (error) {
    result.value[url].check = error.message;
    statistics.value.error++;
  } finally {
    result.value[url].status = 'done';
    totalRequest--;
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
</script>

<template>
  <h1>Crawler 0.82</h1>
  <table class="table table-sm table-bordered">
    <tbody>
      <tr>
        <td class="fixed150">total</td>
        <td><span v-text="statistics.total"></span></td>
      </tr>
      <tr>
        <td>errors</td>
        <td><span v-text="statistics.error"></span></td>
      </tr>
      <tr>
        <td>saved</td>
        <td><span v-text="statistics.saveStatus"></span></td>
      </tr>
      <tr>
        <td>nowReading</td>
        <td><span v-text="statistics.nowReading"></span></td>
      </tr>
    </tbody>
  </table>

  <div v-if="ready">
    <!-- <div class="my-2">
      <LoadingButton :action="saveResults">Сохранить результаты</LoadingButton>
    </div> -->

    <div class="my-2">
      <LoadingButton :action="start" v-if="status == 'start'">Старт</LoadingButton>
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

    <div v-if="status == 'start'">
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

    <div v-if="status == 'start'">
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
              <td><span v-if="val.saveStatus">сохранен</span></td>
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

    <!-- Результаты
    <pre>
      {{ JSON.stringify(result, null, 2) }}
      </pre
    > -->
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

.fixed150 {
  width: 150px;
}
</style>
