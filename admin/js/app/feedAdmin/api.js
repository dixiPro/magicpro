import { apiCall } from '../apiCall.js';

/**
 * Одна точка обращения к API лент.
 *
 * Команда едет в теле запроса, ошибка показывается тостом текстом из errorMsg
 * и бросается дальше — вызывающий решает, что делать с экраном.
 */
const URL = '/a_dmin/api/feed';

export async function apiFeed(data) {
  try {
    const response = await apiCall({
      url: URL,
      data: data,
      logResult: false,
    });

    return response.data;
  } catch (e) {
    document.showToast(e.message ?? e, 'error');

    throw e;
  }
}

/**
 * То же самое, но с файлом.
 *
 * Отдельная функция, а не флаг у apiFeed: файл едет multipart, и json-заголовок
 * с телом из JSON.stringify тут не годятся. Laravel кладёт файл туда же, куда и
 * остальные параметры, поэтому на стороне API разницы нет.
 */
export async function apiFeedFile(data, file) {
  const form = new FormData();

  for (const [key, value] of Object.entries(data)) {
    form.append(key, value);
  }

  form.append('file', file);

  let spinner = null;

  if (typeof document.spinnerServiceShow === 'function') {
    spinner = document.spinnerServiceShow();
  }

  try {
    // Content-Type не ставим: браузер сам допишет его вместе с boundary
    const response = await fetch(URL, { method: 'POST', body: form });

    if (!response.ok) {
      throw new Error(`${response.status} ${response.statusText}`);
    }

    const result = await response.json();

    if (!result.status) {
      throw new Error(result?.errorMsg || 'Неизвестная ошибка');
    }

    return result.data;
  } catch (e) {
    document.showToast(e.message ?? e, 'error');

    throw e;
  } finally {
    if (spinner !== null) document.spinnerServiceHide(spinner);
  }
}
