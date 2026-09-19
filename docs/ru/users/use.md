# Пользователи: как пользоваться

В MagicPro два вида пользователей, и они не пересекаются:

| | Админы MagicPro | Пользователи сайта |
| --- | --- | --- |
| Кто | те, кто работает в админке | посетители, которые регистрируются на сайте |
| Таблица | `magicPro_users` | `users` приложения Laravel |
| Модель | `MagicProUser` пакета | `App\Models\User` хоста |
| Guard | `magic` | стандартный `web` |
| В блейде | `@mproauth`, `auth('magic')->user()` | `@auth`, `auth()->user()` |
| Экран | «Админы» — `/a_dmin/adminList` | «Пользователи» — `/a_dmin/laravelUsers` |

Вошёл в админку — это не значит, что вошёл на сайт, и наоборот.

## Админы MagicPro

### Первый админ

Создаётся из терминала, email и пароль (от 6 символов) вводятся руками:

```bash
php artisan magicpro:admin
```

Без терминала команда ничего не создаёт.

### Вход и выход

Форма входа — на любой странице админки: email, пароль, «запомнить». Пять
неверных паролей подряд на один email с одного адреса — минута паузы. Пустой
пароль не принимается никогда.

Выход — кнопка «Выйти» в шапке админки (форма `POST /a_dmin/logout` с `@csrf`).

### Роли

| Роль | Что может |
| --- | --- |
| `admin` | всё: разделы админки, API, токен МСП |
| `user` | вошёл, но админка закрыта: страницы говорят «нет прав», API отвечает 403 |

Роль `user` видит статьи с `adminOnly` и проходит `@mproauth` — это «свой
человек без прав администратора». Токен МСП выдаётся только `admin`.

Админ с `id = 1` всегда `admin`, удалить его нельзя.

### Экран «Админы»

Список всех админов, добавление, правка имени, email, роли и пароля (пустое
поле — пароль не меняется), удаление.

При добавлении пароль придумывает сервер — 12 букв и цифр — и показывает над
списком **один раз**: скопируйте и передайте. В базе остаётся только хеш,
показать его снова нельзя; забытый пароль меняется правкой.

Пароль админа — не короче 6 символов (правка на экране и `magicpro:admin`).

### В блейде

Для guard админки зарегистрирована директива:

```blade
@mproauth
    <a href="/a_dmin">Admin panel</a>
@else
    <a href="/a_dmin">Sign in</a>
@endmproauth
```

То же самое из PHP:

```php
$admin = auth('magic')->user();
```

Стандартные `@auth`, `@guest` и `auth()` без имени относятся к основному guard
Laravel и могут означать другого пользователя.

## Пользователи сайта

### Экран «Пользователи»

- последние 20 зарегистрированных, сверху новые;
- поиск по части email;
- правка имени, email и пароля (пустое поле — пароль не меняется);
- «войти как» — вы входите на сайт этим пользователем в своём браузере, чтобы
  посмотреть сайт его глазами.

### Настройки регистрации и входа

Там же, над списком, — «Настройки регистрации и входа»:

| Ключ | Что это | Умолчание |
| --- | --- | --- |
| `authUrl` | страница формы: вход, регистрация, «забыли пароль» | `/auth2` |
| `registerUrl` | страница подтверждения регистрации, сюда ведёт ссылка `authLetter` | `/auth2_register` |
| `resetPasswordUrl` | страница смены пароля, сюда ведёт ссылка `resetPasswordLetter` | `/auth2_reset` |
| `postUrl` | куда вести после регистрации или входа, путь сайта | `/` |
| `authLetter` | блейд письма подтверждения email | `magic::auth2_letter_register` |
| `postLetter` | блейд письма после регистрации; пусто — письма нет | `magic::auth2_letter_post` |
| `resetPasswordLetter` | блейд письма смены пароля | `magic::auth2_letter_reset` |
| `emailTokenMinutes` | срок `emailToken` — токена формы между шагами | 30 |
| `registerTokenMinutes` | срок ссылки регистрации | 1440 |
| `resetPasswordTokenMinutes` | срок ссылки смены пароля | 120 |

Блейды — имена для `view()`, например `magic::authLetter`. Нет статьи по адресу
из настроек — ссылки туда никуда не ведут: страницы делает сайт.

### Регистрация и вход на сайте

Готовых страниц нет — это три статьи сайта, которые зовут `API_SiteAuth`:
форма зовёт `POST /api/auth` (с CSRF-токеном страницы), блейд —
`API_SiteAuth::run()`.

**Сценарии.**

- Email не зарегистрирован: форма `authUrl` → `checkEmail` → «Зарегистрировать?»
  → `sendRegisterEmail` → письмо `authLetter` → ссылка на `registerUrl` →
  пароль → `registerUserByToken` → вход, письмо `postLetter`, переход на
  `postUrl`.
- Email зарегистрирован: `checkEmail` → пароль → `auth` → переход на `postUrl`.
- Забыл пароль: `sendChangePasswordEmail` → письмо `resetPasswordLetter` →
  ссылка на `resetPasswordUrl` → новый пароль → `changePassword` → вход,
  переход на `postUrl`.
- Ссылка устарела: «Ссылка устарела, прислать новую?» → `renewLink` → новое
  письмо того же вида.

В базе до перехода по ссылке ничего не создаётся: email, `postUrl` и время лежат
в зашифрованном токене. Любой не просроченный токен работает — человек, который
заказал письмо дважды и нажал на первое, не теряется.

Ответ — `status`, `errorMsg`, `data`, `request`. При отказе в `data.errorCode` —
код, по нему страница выбирает текст.

| Команда | Параметры | В `data` при успехе | `errorCode` |
| --- | --- | --- | --- |
| `checkEmail` | `email`, `gToken` | `emailToken`, `emailActive` | `captcha_failed`, `bad_email` |
| `sendRegisterEmail` | `emailToken`, `gToken`, `postUrl?` | — | `captcha_failed`, `token_invalid`, `token_expired`, `settings_missing`, `letter_already_sent`, `letter_failed` |
| `auth` | `emailToken`, `password` | `postUrl` | `token_invalid`, `token_expired`, `user_not_found`, `wrong_password`, `too_many_attempts` |
| `sendChangePasswordEmail` | `emailToken`, `gToken`, `postUrl?` | — | `captcha_failed`, `token_invalid`, `token_expired`, `user_not_found`, `settings_missing`, `letter_already_sent`, `letter_failed` |
| `registerUserByToken` | `token`, `password?` | `postUrl` | `token_invalid`, `token_expired`, `logged_in_as_other`, `password_required`, `bad_password`, `registration_failed` |
| `changePassword` | `token`, `password` | `postUrl` | `token_invalid`, `token_expired`, `user_not_found`, `bad_password` |
| `renewLink` | `token`, `gToken` | — | `captcha_failed`, `token_invalid`, `settings_missing`, `letter_already_sent`, `letter_failed` |

- `emailActive` — есть ли такой пользователь.
- `gToken` — токен reCAPTCHA из формы.
- `postUrl` — только путь этого сайта (`/cabinet`); всё остальное (`https://…`,
  `//…`) становится `/`. Не передан — берётся из настроек.
- Письмо одного вида на один адрес — не чаще раза в 10 минут
  (`letter_already_sent`). Письмо не ушло — пауза снимается.
- `auth`: 5 неверных паролей подряд на один email с одного адреса — минута паузы.
- Пароль — от 8 символов, пробелы по краям обрезаются.
- `settings_missing` — не заданы адрес страницы или блейд письма.
- `token_invalid` — токен битый; `token_expired` — срок вышел, но email из него
  ещё известен, поэтому `renewLink` работает.

**`registerUserByToken`** по порядку:

1. посетитель уже вошёл под email из токена — успех;
2. вошёл под другим — `logged_in_as_other`;
3. токен протух — `token_expired`;
4. пользователь с этим email есть — тихий вход, успех;
5. нет, пароль не передан — `password_required`;
6. нет, пароль передан — создать (email подтверждён), войти, письмо
   `postLetter`, успех. Письмо не ушло — регистрация всё равно есть, беда в
   логе `api`.

`changePassword` меняет пароль и входит этим пользователем, кто бы ни был
вошедшим до этого.

**Страницы.**

| Статья | Маршрут | Что делает |
| --- | --- | --- |
| `authUrl` | `getEnable: false` | email → `checkEmail` → пароль и `auth` или «Зарегистрировать?» и `sendRegisterEmail`; «Забыли пароль?» → `sendChangePasswordEmail` |
| `registerUrl` | `getEnable: true`, `bindKeys: true`, `keysArr: ["token"]` | блейд зовёт `registerUserByToken(token)` без пароля и реагирует по коду |
| `resetPasswordUrl` | то же | поле нового пароля → `changePassword`; `token_expired` → `renewLink` |

Реакции страницы `registerUrl`:

| Ответ | Что делает страница |
| --- | --- |
| успех | переход на `postUrl` |
| `logged_in_as_other` | «Вы уже авторизованы» |
| `password_required` | поле пароля → `registerUserByToken(token, password)` → `postUrl` |
| `token_expired` | «Ссылка устарела, прислать новую?» → `renewLink` |
| `token_invalid` | «Ссылка неверная» и форма входа |

**Письма** — блейды из настроек, переменные подставляет API:

| Письмо | Переменные |
| --- | --- |
| `authLetter` | `$authLinkUrl` — `registerUrl/<token>` |
| `postLetter` | `$userEmail`, `$userPassword` |
| `resetPasswordLetter` | `$authResetPasswordUrl` — `resetPasswordUrl/<token>` |

`$userPassword` выводить не обязательно: можно написать «пароль, указанный при
регистрации». Тема письма — `<title>` блейда; нет его — `Confirm your email`,
`Password change`, `Registration completed`.

**reCAPTCHA.** Ключи в `.env`: `RECAPTCHA_SITE_KEY` (форма,
`MproHelper::getRecaptureKey()`) и `RECAPTCHA_SECRET_KEY` (сервер). Пустой
секретный ключ — ошибка сервера 500, а не отказ посетителю. Ключи читаются через
`env()`: перед установкой нового ключа сбросьте кеш конфигурации
(`php artisan config:clear`), иначе после `config:cache` ключ будет пустым.

### Форма в попапе и кнопка

Регистрация без ухода со страницы: кнопка открывает попап Bootstrap 5 с формой
входа и регистрации, а вошедшего посетителя ведёт сразу в закрытый раздел.

Это статьи сайта, а не компоненты пакета: в дистрибутиве их нет. Ниже — набор
`auth2_*` с тестового сайта, по нему сайт делает свои.

```blade
<x-magic::auth2_button postUrl="/demo" class="btn btn-success">Получить →</x-magic::auth2_button>

<p>Любой текст страницы.</p>

<x-magic::auth2_button postUrl="/demo" class="btn btn-primary">Хочу →</x-magic::auth2_button>

@guest
  <x-magic::auth2_modal postUrl="/demo" />
@endguest
```

**`auth2_button`** — анонимный компонент.

| Проп | Умолчание | Что это |
| --- | --- | --- |
| `postUrl` | `/` | куда ведёт вошедшего посетителя |
| `modal` | `authModal` | `id` попапа, который открывает гость |

- Посетитель вошёл (`@auth`): `<a href="postUrl">`.
- Не вошёл: `<button data-bs-toggle="modal" data-bs-target="#modal">`.
- Остальные атрибуты (`class` и т. п.) уходят на ссылку или кнопку, текст — из
  слота.

**`auth2_modal`** — анонимный компонент, ставится на страницу один раз, сколько
бы кнопок его ни открывало.

| Проп | Умолчание | Что это |
| --- | --- | --- |
| `postUrl` | `''` | куда вести после входа и после перехода по ссылке из письма; пусто — `postUrl` из настроек |
| `id` | `authModal` | `id` окна; два попапа на странице — разные `id` |
| `title` | `Вход и регистрация` | заголовок окна |

Внутри — та же форма, что на странице `authUrl`, во всю ширину окна. `postUrl`
попапа уходит в `sendRegisterEmail` и `sendChangePasswordEmail`, поэтому ссылка
из письма ведёт туда же. После входа паролем форма сама переходит на `postUrl`
попапа: команда `auth` отдаёт `postUrl` из настроек.

Попап выводить только гостям (`@guest`): вошедшему кнопка его не открывает.

**Что нужно на странице:**

- шаблон с Bootstrap 5 JS (`bootstrap.bundle`) — без него попап не откроется;
- `@stack('styles')` в шаблоне: компонент подключает `auth2_kit` (Alpine,
  reCAPTCHA, вызов `/api/auth`) через стек;
- настроенные страницы `registerUrl` и `resetPasswordUrl` и блейды писем —
  ссылки из писем ведут на них, а не обратно в попап.

**Набор статей `auth2_*`:**

| Статья | Роль | Что делает |
| --- | --- | --- |
| `auth2_kit` | include | Alpine, reCAPTCHA v3, вызов `/api/auth`, тексты по `errorCode` |
| `auth2_form` | include | форма входа и регистрации; `postUrl`, `formClass` |
| `auth2_password` | include | поле пароля с «Показать» |
| `auth2_renew` | include | «Ссылка устарела, прислать новую?» |
| `auth2_modal` | компонент | попап с формой |
| `auth2_button` | компонент | кнопка: вошёл — переход, нет — попап |
| `auth2` | страница | `authUrl` |
| `auth2_register` | страница с контроллером | `registerUrl` |
| `auth2_reset` | страница | `resetPasswordUrl` |
| `auth2_letter_register`, `auth2_letter_post`, `auth2_letter_reset` | блейды писем | `authLetter`, `postLetter`, `resetPasswordLetter` |
| `auth2_demo` | страница | пример попапа с двумя кнопками |

### Остальное: `API_Users`

Из блейда или контроллера статьи — `API_Users::run()`; публичного HTTP нет.

```php
$res = API_Users::run('currentUser', []);
// ['status' => true, 'errorMsg' => '', 'data' => ['auth' => true, 'id' => 5, 'email' => '...', 'name' => '...']]
```

| Команда | Параметры | Что делает |
| --- | --- | --- |
| `currentUser` | — | кто вошёл: `auth`, `id`, `email`, `name` |
| `authEmailPassword` | `email`, `password`, `remember?` | вход без reCAPTCHA |
| `logout` | — | выход |
| `createUser` | `email`, `password`, `name?` | регистрация без письма |
| `userInfo` | `email` | есть ли такой пользователь: `email`, `name`, `created_at` |
| `editUser` | `id`, `email`, `name`, `password?` | правка: свои данные — сам, чужие — только админ MagicPro; `name` не передан — станет пустым |
| `changePassword` | `id`, `password` | смена пароля, права те же |
| `deleteUser` | `email` | удаление; только админ MagicPro |
| `authById` | `id` | «войти как»; только админ MagicPro |
| `getUserList` | `count`, `emailPart` | список для экрана «Пользователи» |

### Осторожно

- `userInfo` не проверяет, кто спрашивает: по email скажет, есть ли такой
  пользователь. Если это не должно быть видно посетителю — не отдавайте ответ
  наружу как есть.
- `@auth` и `auth()` — это пользователь сайта. Админ MagicPro — `@mproauth` и
  `auth('magic')`.
