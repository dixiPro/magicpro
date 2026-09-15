// stores/article.js
import { defineStore } from 'pinia';
import { ref, toRaw, computed, watch } from 'vue';
import { apiArt, translitString } from '../apiCall';
import { formatBlade } from './component/formatBlade.js';
import { formatPhp } from './component/formatPhp.js';
import { convertOldMpro } from './component/convertOldMpro.js';

export const useArticleStore = defineStore('article', () => {
  // data
  const article = ref({});

  // переключатели

  // кнопка транслит он
  const statusTranslitButton = computed(() => {
    if (article.value.name === '') {
      return;
    }
    return /^[a-z0-9_-]+$/i.test(article.value.name);
  });

  // есть твиг
  const hasTwig = computed(() => {
    return /{%\s*.*?\s*%}/.test(article.value.body);
  });

  // скрыт, показать хелп
  const statusHelp = ref(false);

  // панель  настройки
  const statusAddPannel = ref(false);

  // панель  файл менеджера
  const statusFileManager = ref(false);

  const statusAutocompletePannel = ref(false);

  // панель архива версий статьи
  const statusArchive = ref(false);

  // aceTheme
  const aceTheme = ref('chrome');
  const aceThemes = ['chrome', 'monokai', 'dracula', 'twilight'];

  //
  watch(aceTheme, (val) => {
    const darkThemes = ['monokai', 'dracula', 'twilight'];
    if (darkThemes.includes(val)) {
      document.documentElement.setAttribute('data-bs-theme', 'dark');
    } else {
      document.documentElement.setAttribute('data-bs-theme', 'light');
    }
    localStorage.setItem('magic-theme', val);
  });

  // findMode
  const statusLeftPannel = ref('tree');

  // сплиттер редактора контроллер/статься
  const articleReady = ref(false);

  // поиск для контроллера (после поиска по файлам)
  const searchTextController = ref('');
  // для вью
  const searchTextView = ref('');

  // ========= функции

  async function loadRec(id) {
    aceTheme.value = localStorage.getItem('magic-theme') ? localStorage.getItem('magic-theme') : 'chrome';

    const art = await apiArt({ command: 'getById', id });
    article.value = updateRouteParams(art);
    history.pushState(id, null, '#' + id);
    articleReady.value = true;
    document.title = article.value.title;
  }

  // the same defaults as Article::ROUTE_PARAMS on the server
  function updateRouteParams(art) {
    let routeParams = art.routeParams;
    if (Array.isArray(routeParams) || routeParams === null || typeof routeParams !== 'object') {
      routeParams = {};
    }
    const {
      //
      adminOnly = false,
      useController = false,
      getEnable = false,
      utmParamsEnable = true,
      bindKeys = false,
      postEnable = false,
      keysArr = [],
    } = routeParams;

    art.routeParams = {
      useController,
      adminOnly,
      getEnable,
      postEnable,
      utmParamsEnable,
      bindKeys,
      keysArr,
    };

    return art;
  }

  async function saveRec() {
    const saved = await apiArt({
      command: 'saveById',
      article: article.value,
    });

    // статья сохранена всегда, а вот файлы пишутся, только если контроллер
    // проходит проверку синтаксиса. Не прошёл — текст в базе твой, а страница
    // пока работает на прежнем контроллере, и об этом надо сказать вслух
    const warning = saved.warning ?? '';
    delete saved.warning;

    article.value = saved;

    warning
      ? document.showToast('Сохранено, но контроллер не опубликован: ' + warning, 'error')
      : document.showToast('Сохранено');
  }

  async function getController() {
    try {
      const res = await apiArt({ command: 'getDefaultController', id: 1 });
      article.value.controller = res.controller;
    } catch (e) {
      console.log(e);
    }
  }

  async function gotoArticleByName(name) {
    try {
      const result = await apiArt({ command: 'articleByName', name });
      window.open(location.pathname + '#' + result.id, '_blank');
    } catch (e) {}
  }

  async function getLiveWareController() {
    try {
      const res = await apiArt({
        command: 'getDefaultLiveWareController',
        id: 1,
      });
      article.value.controller = res.controller;
    } catch (e) {
      console.log(e);
    }
  }

  async function search(text) {
    try {
      const res = await apiArt({ command: 'search', text: text });
      return res;
    } catch (e) {
      console.log(e);
    }
  }

  async function formatDocument() {
    const result = await formatBlade(article.value.body, 2);
    article.value.body = result;

    const result1 = await formatPhp(article.value.controller, 2);
    article.value.controller = result1;
    document.showToast('Отформатировано');
    try {
    } catch (error) {
      document.showToast('Ошибка форматирования: ' + error.message, 'error');
    }
  }

  function translit() {
    article.value.name = translitString(article.value.title.trim());
  }

  // горячие кравиши
  const handleKeydown = (event) => {
    if (event.ctrlKey && event.code === 'KeyS') {
      event.preventDefault(); // Prevent browser's default save action
      saveRec();
      return;
    }
    // Alt+3…Alt+6 were here and pointed at panels the store no longer has:
    // a press threw a ReferenceError. The help does not show them any more
  };

  function convertFromMro() {
    article.value.body = convertOldMpro(article.value.body);
  }

  const toggleTreeSplitter = ref();
  const modeTreeSplitter = ref('open');

  const toggleEditorSplitter = ref();
  const modeEditorSplitter = ref('full');

  // инициализация
  window.addEventListener('keydown', handleKeydown);

  return {
    article,
    statusHelp,
    statusAddPannel,
    statusFileManager,
    aceTheme,
    aceThemes,
    hasTwig,
    statusTranslitButton,
    hasTwig,
    statusLeftPannel,
    statusAutocompletePannel,
    statusArchive,

    toggleTreeSplitter,
    modeTreeSplitter,
    toggleEditorSplitter,
    modeEditorSplitter,

    searchTextController,
    searchTextView,

    articleReady,

    loadRec,
    saveRec,
    getController,
    getLiveWareController,
    gotoArticleByName,
    formatDocument,
    translit,
    convertFromMro,
    search,
  };
});
