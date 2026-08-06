/**
 * Дата строкой по маске.
 *
 * Буквы взяты у PHP и означают то же самое, поэтому маску из схемы можно без
 * перевода поставить в блейд. Поддерживается их часть: d m y Y H i s.
 *
 * Испорченное значение возвращается как есть: пусть оператор видит, что лежит
 * в поле, а не NaN.NaN.NaN.
 */
const TOKENS = /[dmyYHis]/g;

export function formatDate(value, format = 'Y-m-d H:i:s') {
  if (value === null || value === undefined || value === '') return '';

  // в базе дата с пробелом, а такой формат разбирают не все браузеры
  const date = value instanceof Date ? value : new Date(String(value).replace(' ', 'T'));

  if (isNaN(date)) return String(value);

  const pad = (number) => String(number).padStart(2, '0');

  const parts = {
    d: pad(date.getDate()),
    m: pad(date.getMonth() + 1),
    y: String(date.getFullYear()).slice(-2),
    Y: String(date.getFullYear()),
    H: pad(date.getHours()),
    i: pad(date.getMinutes()),
    s: pad(date.getSeconds()),
  };

  return format.replace(TOKENS, (token) => parts[token]);
}
