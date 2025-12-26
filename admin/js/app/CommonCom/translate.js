// import { useI18n } from 'vue-i18n';
// const { t } = useI18n();

//  t('save')
//  {{ t('reset') }}

import { apiSetup, apiCall } from '../apiCall';

import { createI18n } from 'vue-i18n';

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  fallbackLocale: 'en',
  messages: {
    en: {
      yes: 'Yes',
      no: 'No',
      'delete?': 'Delete?',
      enter_number: 'Enter a number',
      save: 'Save',
      cancel: 'Cancel',
      reset: 'Reset',
      reseted: 'Settings have been reset',
      Saved: 'Saved',
      file_saved: 'File saved',
      formatted: 'Formatted',
      file_name: 'File name',
    },

    ru: {
      //
      yes: 'Да',
      no: 'Нет',
      delete: 'Удалить',
      enter_number: 'Введите число',
      save: 'Сохранить',
      cancel: 'Отмена',
      reset: 'Сбросить',
      reseted: 'Параметры сброшены',
      Saved: 'Saved',
      file_saved: 'Файл сохнанен',
      formatted: 'Отформатировано',
      file_name: 'Имя файла',
      was_deleted: 'Удален ',
      file_was_loaded: 'Загружен',
      copy_path: 'Копировать путь',
      copy_as_imgcode: 'Копировать Img',
      edit: 'Редактировать',
      rename: 'Переименовать',
      cancel: 'Отмена',
      create_folder: 'Создать папку',
      name_folder: 'Имя папки',
      create: 'Создать',
      ready: 'Готово',
      new_name: 'Новое имя',
      submit: 'Готово',
      upload_files: 'Загрузка файлов',
      choose_files: 'Выбрать файлы',
      upload: 'Загрузить',
      duplicate_overwrite: 'копия — будет переписан',

      crawler: 'Краулер',
      total: 'Всего',
      errors: 'Ошибок',
      saved: 'Сохранено',
      now_reading: 'Сейчас проверяется',

      start: 'Старт',
      stop: 'Стоп',

      cache_created: 'Кеш создан',
      cache_published: 'Кеш опубликован',

      delete_storage: 'Удалить storage',
      publish_storage: 'Опубликовать storage',
      delete_published_cache: 'Удалить опубликованный кеш',

      errors: 'Ошибки',
      ok: 'Ок',

      saved_lower: 'сохранен',

      toast_storage_deleted: 'Storage удален',
      toast_public_deleted: 'Public удален',
      toast_cache_published: 'Кеш опубликован',

      error: 'Ошибка',
      user_list_loaded: 'Список считан',
      add: 'Добавить',
      error: 'Ошибка',

      go_selected: 'перейти в статье по выбранному слову',
      blade_snippet: 'снипеты блейда',
      blade_comment: 'комментарии блейда',

      route_params: 'Параметры маршрута',
      use_controller: 'Испольовать контроллер',
      menu_on: 'Видно в меню',
      is_route: 'Раут включен',
      only_admin: 'Только Админ',
      utm_enable: 'Разрешить Utm ?=utm...',
      use_getpatams: 'Использовать GET параметры',
      add_route_params: 'Добавить параметр',
      available_all_params: 'Доступны любые параметры',
      available_selected_params: 'Доступны только',
      bind_params: 'Привязывать параметры',
      autocomplete_controller: 'Автозаполнение',
      autocomplete_simple_controller: 'Контроллер',
      autocomplete_liveWare_controller: 'LiveWare контроллер',
      ready_url_rout: 'Готовый url',

      goto_article: 'перейти на статью',
    },
  },
});

export const i18nReady = new Promise((resolve) => {
  (async () => {
    const result = await apiSetup({
      command: 'getIniParams',
    });
    const loc = result?.LANGUAGE || 'en'; //
    console.log(loc);
    i18n.global.locale.value = loc;
    resolve(loc);
  })();
});

export default i18n;
