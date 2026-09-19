# Пользователи: устройство

Две независимые системы учётных записей: админы MagicPro и пользователи сайта.
Общего у них только то, что обе стоят на механизме auth Laravel.

## Карта файлов

| Файл | Что делает |
| --- | --- |
| `database/Models/MagicProUser.php` | модель админа, проверки при сохранении |
| `database/migrations/create_magicPro_users_table.php` | таблица админов |
| `src/MagicServiceProvider.php` | guard `magic`, provider `magic_users`, `@mproauth`, alias `magic.auth`, alias классов `API_SiteAuth`, `API_Users` |
| `admin/middleware/CheckMagicAuth.php` | middleware `magic.auth[:роли]` |
| `admin/controller/AuthController.php` | вход и выход админки |
| `admin/controller/API_EditUsersController.php` | API экрана «Админы» |
| `admin/controller/AdminController.php::adminList()` | страница «Админы» |
| `admin/js/app/EditUsers/EditUsers.vue` | экран «Админы» |
| `src/Console/AdminCommand.php` | `php artisan magicpro:admin` |
| `src/Api/API_SiteAuth.php` | публичное API регистрации и входа посетителей, `POST /api/auth` |
| `src/Api/API_Users.php` | пользователи сайта: список, правка, «войти как», удаление; экран «Пользователи» |
| `src/Api/SiteUserTools.php` | общее двух API: проверка email и пароля, вход с лимитом попыток |
| `src/Api/ApiError.php` | исключение с `errorCode` для `AbstractApi` |
| `src/Config/magicSchema.php`, группа `AUTH` | настройки регистрации и входа |
| `admin/js/app/EditLaravelUsers/AuthSettings.vue` | блок настроек на экране «Пользователи» |
| `admin/js/app/EditLaravelUsers/EditLaravelUsers.vue` | экран «Пользователи» |
| `app/Models/User.php` хоста | модель пользователя сайта — берётся у приложения |

## Структура данных

### `magicPro_users` — админы

| Колонка | Тип | Null | Умолчание | Что хранит |
| --- | --- | --- | --- | --- |
| `id` | bigint, PK | нет | авто | `1` — главный админ |
| `name` | string | да | `null` | имя; модель требует непустое |
| `email` | string, unique | нет | — | логин |
| `password` | string | нет | — | bcrypt-хеш |
| `role` | string(50) | да | `null` | `admin` или `user` |
| `remember_token` | string(100) | да | `null` | «запомнить меня» |
| `created_at`, `updated_at` | timestamp | да | Laravel | |

Модель `MagicProUser` (`Authenticatable`): `fillable` — `name`, `email`,
`password`, `role`; скрыты `password`, `remember_token`. Каста пароля нет —
хешируют вызывающие (`Hash::make`, `bcrypt`).

При `saving`:

- у `id = 1` роль принудительно `admin`;
- `validateSelf()`: `name` обязателен, до 255; `email` — формат и уникальность;
  `role` — `null`, `admin` или `user`. Ошибка — `InvalidArgumentException` со
  списком полей. Пароль модель не проверяет: до неё доходит хеш, а
  `attributesToArray()` скрытые поля и не отдаёт.

`MagicProUser::PASSWORD_MIN = 6` — минимальная длина пароля админа. Проверяют её
там, где пароль ещё текст: `magicpro:admin` и `editUser` экрана «Админы».

При `deleting` у `id = 1` — `RuntimeException`.

### `users` — пользователи сайта

Таблица и модель — приложения Laravel, пакет их не создаёт. Ожидается
стандартная схема Laravel 13:

| Колонка | Тип | Что хранит |
| --- | --- | --- |
| `id` | bigint, PK | |
| `name` | string, not null | имя; `API_SiteAuth` и `API_Users::createUser` пишут `''`, если не передано |
| `email` | string, unique | логин, в нижнем регистре |
| `email_verified_at` | timestamp, null | ставит `API_SiteAuth::registerUserByToken` — пользователь пришёл по ссылке из письма; прямой `createUser` оставляет `null` |
| `password` | string | хеш; у модели хоста каст `hashed` |
| `remember_token` | string, null | |
| `created_at`, `updated_at` | timestamp | |

Список полей экрана «Пользователи» строится из `fillable` модели хоста и
`Schema::getColumns()` (`getStructure`), пароль из него выкидывается.

## Guard и middleware

В `boot()` провайдера:

```php
Config::set('auth.providers.magic_users', ['driver' => 'eloquent', 'model' => MagicProUser::class]);
Config::set('auth.guards.magic', ['driver' => 'session', 'provider' => 'magic_users']);
Blade::if('mproauth', fn () => Auth::guard('magic')->check());
$router->aliasMiddleware('magic.auth', CheckMagicAuth::class);
```

Сессия у обоих guard одна (cookie сайта), ключи в ней разные.

`CheckMagicAuth`:

- не вошёл в `magic` — JSON 401 `MagicPro authorization required`;
- роли из параметра (`magic.auth:admin,user`), без параметра — только `admin`;
  роль не та — JSON 403 `Insufficient rights`.

Страницы админки middleware не имеют: блейд сам проверяет
`Auth::guard('magic')->user()?->role === 'admin'` и иначе пишет «нет прав».

Где ещё смотрят на `magic`:

- `adminOnly` статьи — `Auth::guard('magic')->check()`, любая роль;
- `TokenMiddleware` МСП — владелец токена существует и `role === 'admin'`;
- `API_Users::authById`, `deleteUser`, `checkUserAccess` — `role === 'admin'`.

## Вход и выход админки

`AuthController::login` (`POST /a_dmin/login`, форма в `templateAdmin`):

1. email — `trim` и нижний регистр;
2. `RateLimiter`, ключ `magic-login:<email>|<ip>`: 5 неудач подряд — пауза 60
   секунд, `mpro_error` «too many attempts, try again in N s»;
3. пустой пароль — отказ сразу (хеш пустой строки принял бы пустое поле);
4. `Hash::check`, `Auth::guard('magic')->login($user, remember)`,
   `RateLimiter::clear`, `session()->regenerate()`, редирект назад;
5. неудача — `RateLimiter::hit`, редирект назад с `mpro_error`.

`logout` — `POST /a_dmin/logout` с csrf, форма-кнопка в шапке
`templateAdmin`; `Auth::guard('magic')->logout()`, редирект назад. Сессия не
сбрасывается целиком: в ней может быть вход пользователя сайта.

`GET /login` Laravel перенаправлен на `/`.

## API экрана «Админы»

`POST /a_dmin/api/editUsers`, `magic.auth:admin`, CSRF. Своя обёртка ответа:
`status`, `data`, `errorMsg`, `request`; в `errorMsg` только текст ошибки, файл и
строка — в лог `api`.

| Команда | `data` на входе | Что делает |
| --- | --- | --- |
| `getUserList` | — | все админы по `id`: `id`, `name`, `email`, `role`, даты |
| `addUser` | `name`, `email`, `role` | создаёт; пароль — `Str::password(12, symbols: false)`, в ответе один раз открытым текстом (`password`), в базе хеш |
| `editUser` | `id`, `name`, `email`, `role?`, `password?` | правит; непустой пароль — от `PASSWORD_MIN`, `Hash::make` |
| `deleteUser` | `id` | удаляет; `id = 1` — отказ |

## Коды ошибок в `AbstractApi`

Команда бросает `ApiError($errorCode, $message)` — `run()` кладёт код в
`data.errorCode`, текст в `errorMsg`. `runOrFail()` пробрасывает `ApiError` с
тем же кодом. Исключение HTTP (`abort()`) `run()` не глотает: пишет в лог `api`
и отдаёт Laravel — так пустой ключ reCAPTCHA становится ответом 500.

## `API_SiteAuth` — регистрация и вход посетителей

HTTP: `POST /api/auth` (`routes/site.php`, группа `web`, CSRF проверяется),
alias `API_SiteAuth`. Модель — `App\Models\User` хоста, guard — `web`.

### Настройки

Группа `AUTH` схемы с пометкой `'page' => 'users'`: «Настройки» (`Setup.vue`)
параметры с `page` пропускают, их правит `AuthSettings.vue` на экране
«Пользователи». Файл настроек пишется только целиком (`saveIniFile` возвращает
пропущенные ключи к умолчаниям), поэтому блок читает `getIniParams`, правит
`AUTH` и сохраняет всё через `saveIniParams`. Пока настройки не сохранены с
новой группой, `setting()` берёт умолчание из схемы.

### Токены

```text
json {type, email, postUrl, time}  →  Crypt::encryptString  →  base64url без '='
```

| `type` | Выдаёт | Срок |
| --- | --- | --- |
| `email` | `checkEmail` | `emailTokenMinutes` |
| `register` | письмо `authLetter` | `registerTokenMinutes` |
| `reset` | письмо `resetPasswordLetter` | `resetPasswordTokenMinutes` |

`readToken($token, $types)`: не расшифровался, не тот `type`, нет полей —
`token_invalid`. Срок сверяется с `time` и настройкой в момент чтения и
возвращается флагом `expired`: протухший токен остаётся читаемым, поэтому
`renewLink` знает email. `readLiveToken` превращает `expired` в `token_expired`.
Пароля в токене нет. Токен не одноразовый: в пределах срока ссылка работает
повторно.

`postUrl` — `localPath()`: путь своего сайта, начинается с `/`, не с `//` и не
с `/\`, без управляющих символов; иначе `/`. Проверяется и при выдаче, и при
чтении токена.

### Письма со ссылкой

`sendLinkLetter($type, $email, $postUrl)` — общая часть `sendRegisterEmail`,
`sendChangePasswordEmail`, `renewLink`:

1. адрес страницы и блейд из настроек, пусто — `settings_missing`;
2. `MagicProEvent::addEvent("mail_<email>_registration"` или
   `"mail_<email>_reset_password", +10 минут)`; событие есть —
   `letter_already_sent`;
3. ссылка `url(<страница>/<токен>)`, рендер `view($blade, [$authLinkUrl|$authResetPasswordUrl])`;
4. `MproHelper::sendMail`, тема — `<title>` письма или умолчание;
5. рендер или отправка упали — событие удаляется, `letter_failed`.

`postLetter` после регистрации: пустой блейд — не отправляется; ошибка — в лог
`api`, регистрация не откатывается.

### reCAPTCHA

`API_SiteAuth::verifyCaptcha($token)` — публичный, его же зовёт
`MproHelper::verifyRecapture`. `env('RECAPTCHA_SECRET_KEY')` пустой —
`abort(500)`. Пустой токен, ошибка сети, ответ Google без `success` — `false`,
в командах — `captcha_failed`. Капча проверяется первой, до токенов и базы.

### Вход

`SiteUserTools::attemptLogin`: `RateLimiter`, ключ `login:<email>|<ip>`,
5 неудач — пауза 60 секунд (`too_many_attempts`), пустой пароль — неудача
(`wrong_password`); после входа `session()->regenerate()`. Вход по ссылке
(`registerUserByToken`, `changePassword`) — `Auth::login($user, remember)` и
тот же `regenerate()`. «Запомнить» — всегда.

## `API_Users` — пользователи сайта

Наследник `AbstractApi`, alias `API_Users`. HTTP только для экрана:
`POST /a_dmin/api/laravelUsers`, `magic.auth` — админ.

### Проверки

- email: `trim`, нижний регистр, `email:rfc,dns`, до 255;
- пароль: `trim`, 8–255;
- `authEmailPassword` — тот же `attemptLogin`, что у `API_SiteAuth`.

### Права в командах

- `editUser`, `changePassword` — `checkUserAccess`: сам пользователь (`Auth::id()`)
  или админ MagicPro с ролью `admin`;
- `authById`, `deleteUser` — только админ MagicPro;
- `userInfo`, `createUser` — без проверки: решает вызывающий код.

## Первый админ

`magicpro:admin` — только в терминале (`isInteractive()`), email через `ask`,
пароль через `secret`, от `MagicProUser::PASSWORD_MIN` (6) символов, роль `admin`, имя `Admin`. Проверки
email — моделью.
