import { apiCall } from '../apiCall.js';

/**
 * Одна точка обращения к API агента.
 *
 * Команда едет в теле запроса, ошибка показывается тостом и бросается дальше.
 */
const URL = '/a_dmin/api/mcp';

export async function apiMcp(data) {
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
 * То же самое, но молча: без спиннера и без тоста.
 *
 * Опрос вывода идёт раз в секунду и живёт всё время сеанса. Общий apiCall
 * зажигал бы на каждый такой запрос спиннер, а тост об ошибке сети превратился
 * бы в ленту одинаковых сообщений. Что делать с ошибкой, решает опрос: он
 * останавливается и показывает её один раз.
 */
export async function apiMcpQuiet(data) {
  const response = await fetch(URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    throw new Error(`${response.status} ${response.statusText}`);
  }

  const result = await response.json();

  if (!result.status) {
    throw new Error(result?.errorMsg || `${response.status}`);
  }

  return result.data;
}
