/**
 * Буфер вырезанной ленты. Живёт между экранами: вырезают на списке лент
 * группы, вставляют на списке групп.
 *
 * Не реактивный: контекстное меню собирается заново на каждый клик, следить за
 * изменениями некому. Отмены нет — буфер очищается вставкой или перезагрузкой
 * страницы.
 */
let cutFeed = null;

export function setCutFeed(feed) {
  cutFeed = feed;
}

export function getCutFeed() {
  return cutFeed;
}

export function clearCutFeed() {
  cutFeed = null;
}
