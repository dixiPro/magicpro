import { apiCall } from '../apiCall.js';

/**
 * Одна точка обращения к API лент.
 *
 * Команда едет в теле запроса, ошибка показывается тостом текстом из errorMsg
 * и бросается дальше — вызывающий решает, что делать с экраном.
 */
export async function apiFeed(data) {
  try {
    const response = await apiCall({
      url: '/a_dmin/api/feed',
      data: data,
      logResult: false,
    });

    return response.data;
  } catch (e) {
    document.showToast(e.message ?? e, 'error');

    throw e;
  }
}
