// stores/article.js
import { defineStore } from 'pinia';
import { ref, toRaw, computed } from 'vue';
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

  // aceTheme
  const aceTheme = 'chrome';
  const aceThemes = ['chrome', 'monokai', 'dracula', 'twilight'];

  // findMode
  const statusLeftPannel = ref('find');

  // сплиттер редактора контроллер/статься
  const articleReady = ref(false);

  const searchTextController = ref('');
  const searchTextView = ref('');

  // ========= функции

  async function loadRec(id) {
    const art = await apiArt({ command: 'getById', id });
    article.value = updateRouteParams(art);
    history.pushState(id, null, '#' + id);
    articleReady.value = true;
  }

  function updateRouteParams(art) {
    let routeParams = toRaw(art.routeParams);
    if (Array.isArray(routeParams) || routeParams === null || typeof routeParams !== 'object') {
      routeParams = {};
    }
    const { adminOnly = false, useController = true, getEnable = false, utmParamsEnable = true, bindKeys = false, keysArr = [] } = routeParams;

    art.routeParams = {
      useController,
      adminOnly,
      getEnable,
      utmParamsEnable,
      bindKeys,
      keysArr,
    };

    return art;
  }

  async function saveRec() {
    article.value = await apiArt({
      command: 'saveById',
      article: article.value,
    });
    document.showToast('Сохранено');
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

  // горчие кравиши
  const handleKeydown = (event) => {
    if (event.ctrlKey && event.code === 'KeyS') {
      event.preventDefault(); // Prevent browser's default save action
      saveRec();
      return;
    }
    // левое меню
    if (event.altKey && event.code === 'Digit4') {
      addPannel.value = !addPannel.value;
      event.preventDefault(); // Prevent browser's default save action
      return;
    }
    // блейд 99%
    if (event.altKey && event.code === 'Digit6') {
      splitStatusEditorObj.value.set('hideController');
      event.preventDefault(); // Prevent browser's default save action
      return;
    }
    // контроллер 99%
    if (event.altKey && event.code === 'Digit5') {
      splitStatusEditorObj.value.set('hideBlade');
      event.preventDefault(); // Prevent browser's default save action
      return;
    }
    // дерево 99%
    if (event.altKey && event.code === 'Digit3') {
      const status = splitTreeStatusObj.value.status == 'hideTree' ? 'normal' : 'hideTree';
      splitTreeStatusObj.value.set(status);
      event.preventDefault(); // Prevent browser's default save action
      return;
    }
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
