# Админка почтовой системы

## Как устроена админка

Админка почтовой системы это одна из админок

### В качестве примера смотри

packages\dixipro\magicpro\admin\js\editLaravelUsers.js

### Подключение vite

файл packages\dixipro\magicpro\vite.config.js

Секция
laravel({
input: [
'admin/js/artEditor.js', //
....
],
Больше в packages\dixipro\magicpro\vite.config.js ничего не трогай!!!

### Левое меню админки тут

packages\dixipro\magicpro\admin\views\leftColumn.blade.php

### Пример блейда тут

packages\dixipro\magicpro\admin\views\users.blade.php

### Стек админки

Vue, собственные компоненты, PrimeVue.
Обрати внимание на собственные компоненты.
packages\dixipro\magicpro\admin\js\app\CommonCom
По возможности используй их.
Если в них ошибка, обязательно запрос на ее изменение. Не правь тихо без предупреждения.

### Тосты и диалоги

изучи и используй
packages\dixipro\magicpro\admin\js\app\CommonCom\ToastConfirm.vue

### Двуязычность интерфейса

import { useI18n } from 'vue-i18n';
const { t } = useI18n();

### API

Все обращение идет через import { apiCall } from '../apiCall.js';
Для конкретной админки можно сделать обертку этой функции.

### Раут админки

packages\dixipro\magicpro\admin\web.php

перед изменением обязательно запрос
Не трогай те рауты, которые не относятся к заданию.

### Иконки

Использовать Авефонтс5, он уже подключен и дополнительного подключения не требуется.

### Верстка

Испольуется Bootstrap 5. он уже подключен и дополнительного подключения не требуется.

## Задание

Спроектировать админку для почтовой системы. ТЗ в файле MailSystem.md

## интерфейс

разделы
Отправленные / В очереди
Строка поиска
Список емайлов

Отображаемые поля
'from_email',
'to_email',
'subject',
'sent_at',
'attempts'
'status',
иконка ошибки
иконка удалить

для раздела В очереди добавить scheduled_at

Выводится список

## Ответы на вопросы

1. **Состав разделов по статусам.**
   - «В очереди» = `queued` + `error` (ждут ретрая)
   - «Отправленные» = все остальные

2. В `API_Mail` добавить команду `messagesList`
   с параметрами: согласно разделам по статусам + `count` (по умолчанию 30) + `offset`.

3. **Поле поиска.** Искать по `to_email`

4. **Иконка ошибки.** По клику показывать содержимое `errors` (JSON-история
   попыток) — в модальном окне.

5. **Удаление.** Иконка «удалить» в обоих разделах вызывает `deleteEmail` по
   `id` с подтверждением (`document.confirmDialog`)

6. **Роут и меню.**
   - раут на админку`/a_dmin/mailSystem` (`magic.mail`)
   - раут на апи `POST /a_dmin/api/mailSystem` → `API_Mail::handle`

7. Добавить пункт левого меню (иконка `fas fa-envelope`, label `mail`)? Правка `web.php` и `leftColumn`

## План действий

2. Бэкенд: добавить в `API_Mail` команду `messagesList` (раздел + поиск +
   пагинация) и зарегистрировать её в `$map`. `deleteEmail` уже есть.
3. Роутинг: зарегистрировать страницу `/a_dmin/mailSystem` (`magic.mail`) и API
   `POST /a_dmin/api/mailSystem` → `API_Mail::handle` в `admin/web.php`
   (по образцу `laravelUsers`, с `magic.auth` и `withoutMiddleware([$csrf])`).
4. Vite: добавить точку входа `admin/js/mailSystemAdmin.js` в секцию `input`
   (только добавить строку, ничего больше не трогать).
5. Blade: создать `admin/views/mailSystem.blade.php` по образцу `users.blade.php`
   (монтирование `#mailSystemAdmin` + `@vite`).
6. JS-бут: `admin/js/mailSystemAdmin.js` по образцу `editLaravelUsers.js`
   (PrimeVue, ToastService, ConfirmationService, Dialog, i18n, монтаж в
   `#mailSystemAdmin`).
7. Vue-компонент `admin/js/app/mailSystemAdmin/mailSystemAdmin.vue`:
   - табы «Отправленные / В очереди»;
   - строка поиска с debounce;
   - список с полями `from_email`, `to_email`, `subject`, `sent_at`,
     `attempts`, `status`, иконка ошибки, иконка удалить; для «В очереди»
     дополнительно `scheduled_at`;
   - вызовы API через локальную обёртку `apiMail` (по образцу `apiLaravelUsers`);
   - удаление через `document.confirmDialog` + `document.showToast`;
   - двуязычность через `useI18n` / `t`.
8. Переводы: добавить ключи (разделы, статусы, заголовки колонок, подтверждение
   удаления) в `admin/js/app/CommonCom/translate.js`.
9. Левое меню: добавить пункт в `admin/views/leftColumn.blade.php`.
10. Проверка: `php -l` для PHP, `npm run dev`/`build` для сборки фронта.
    Общие компоненты из `CommonCom` использовать как есть; при необходимости
    правки — отдельный запрос, тихо не менять.

## Файлы

**Создать:**

- `admin/views/mailSystem.blade.php` — страница-контейнер админки почты.
- `admin/js/mailSystemAdmin.js` — точка входа Vue-приложения.
- `admin/js/app/mailSystemAdmin/mailSystemAdmin.vue` — основной компонент (табы, поиск,
  список, удаление).

**Изменить:**

- `src/Mail/API_Mail.php` — добавить команду `messagesList` + запись в `$map`
  (после согласования вопроса 1).
- `admin/web.php` — роуты страницы и API (после согласования).
- `vite.config.js` — добавить `admin/js/mailSystemAdmin.js` в `input`.
- `admin/views/leftColumn.blade.php` — пункт меню.
- `admin/js/app/CommonCom/translate.js` — ключи перевода.

**Не трогаю:** `vendor/dixipro/magicpro/**`, остальную часть `vite.config.js`,
чужие роуты в `web.php`, общие компоненты `CommonCom` (правка — только по
запросу).

## Статус: реализовано

Все пункты плана выполнены. Ниже — справочник по факту реализации, чтобы
следующую такую админку писать быстрее (реальные пути, паттерны, подводные
камни).

## Справочник: как собрать новую админку (проверено на почте)

### Два РАЗНЫХ механизма перевода — не путать

- **Пункт левого меню** (`leftColumn.blade.php`, директива `@magic_msg`) берёт
  текст из **PHP-переводов**: `lang/en/messages.php` и `lang/ru/messages.php`.
  Ключ меню надо добавить именно туда, в `translate.js` его класть бесполезно.
- **Всё внутри Vue-компонента** (`t('...')`, `useI18n`) берёт текст из
  **JS-переводов**: `admin/js/app/CommonCom/translate.js` (блоки `en:` и `ru:`).
  Добавлять ключ надо в оба блока.

### Роуты (`admin/web.php`)

Вверху файла уже определена переменная `$csrf`. Паттерн пары «страница + API»:

```php
use MagicProSrc\Mail\API_Mail;

// страница
Route::get('/a_dmin/mailSystem', function () {
    return view('magicAdmin::mailSystem');
})->name('magic.mail');

// АПИ (класс API уже умеет ->handle(): наследует AbstractMailApi/AbstractApi)
Route::post('/a_dmin/api/mailSystem', [API_Mail::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);
```

`->middleware('magic.auth:admin')` — если раздел только для админа.

### Blade-страница (`admin/views/*.blade.php`)

```blade
@extends('magicAdmin::templateAdmin')
@section('title', 'Mail')
@section('body')
    @if (Auth::guard('magic')->user()->role === 'admin')
        <div id="mailSystemAdmin"></div>
        @vite('admin/js/mailSystemAdmin.js', 'vendor/dixipro/magicpro')
    @else
        <div>@magic_msg('no_permissions')</div>
    @endif
@endsection
```

`id` контейнера должен совпадать с `app.mount('#...')` во входном JS.

### Точка входа JS (`admin/js/*.js`)

Копия `editLaravelUsers.js`: `createApp(Component)`, регистрация нужных
компонентов PrimeVue (для списков с модалкой нужен `Dialog`), затем
`app.use(PrimeVue|ConfirmationService|ToastService|i18n)` и
`app.mount('#id')`. `i18n` — это `import i18n from './app/CommonCom/translate.js'`.

### Vue-компонент — обязательные привычки

- API только через локальную обёртку по образцу `apiLaravelUsers`: guard
  `apiActive`, `apiCall({url, data})`, `catch → document.showToast(e,'error')`,
  возврат `response.data`. `apiCall` сам кидает исключение, если `status:false`
  (сообщение берёт из `errorMsg`), и возвращает `{status, errorMsg, data}`.
- Команда уходит в `data.command`, разбирается в `$map` класса API.
- Глобальные хелперы приходят из `CommonCom/ToastConfirm.vue`, который надо
  вставить в шаблон как `<TosatConfirm></TosatConfirm>`:
  - `document.showToast(msg, severity='success'|'error')`
  - `await document.confirmDialog(msg)` → `Promise<boolean>` (Да/Нет)
- Даты из БД приходят как `2026-07-18T15:09:24.000000Z` — резать хелпером
  `formatDate` (regex `^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2}:\d{2})`).
- Поиск — `@input` + `setTimeout` debounce 300мс.
- Вёрстка — Bootstrap 5, иконки — FontAwesome 5 (`fas fa-*`), оба уже подключены.

### Vite

В `vite.config.js` в массив `input` добавить ТОЛЬКО одну строку — путь ко
входному JS. Больше в файле ничего не трогать.

### API-слой (`src/Mail/API_Mail.php`)

Команда листинга для админки — `messagesList(section, search, count, offset)`:
`section='queue'` → `whereIn(QUEUE_STATUSES)` (queued+error), иначе
`whereNotIn(...)`; поиск `to_email like %search%`; отдаёт
`{section, total, count, offset, messages}`. `errors` включены в выборку, чтобы
модалка ошибок не делала лишний запрос. Удаление — существующий `deleteEmail`
по `id`.
