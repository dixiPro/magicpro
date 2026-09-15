# Пользователи: устройство

Две независимые системы учётных записей: админы MagicPro и пользователи сайта.
Общего у них только то, что обе стоят на механизме auth Laravel.

## Карта файлов

| Файл | Что делает |
| --- | --- |
| `database/Models/MagicProUser.php` | модель админа, проверки при сохранении |
| `database/migrations/create_magicPro_users_table.php` | таблица админов |
| `src/MagicServiceProvider.php` | guard `magic`, provider `magic_users`, `@mproauth`, alias `magic.auth`, alias класса `API_Auth` |
| `admin/middleware/CheckMagicAuth.php` | middleware `magic.auth[:роли]` |
| `admin/controller/AuthController.php` | вход и выход админки |
| `admin/controller/API_EditUsersController.php` | API экрана «Админы» |
| `admin/controller/AdminController.php::adminList()` | страница «Админы» |
| `admin/js/app/EditUsers/EditUsers.vue` | экран «Админы» |
| `src/Console/AdminCommand.php` | `php artisan magicpro:admin` |
| `src/Api/API_Auth.php` | пользователи сайта: вход, регистрация, правка, экран «Пользователи» |
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
| `name` | string, not null | имя; `API_Auth` пишет `''`, если не передано |
| `email` | string, unique | логин, в нижнем регистре |
| `email_verified_at` | timestamp, null | ставит `processNewUser` — пользователь пришёл по ссылке из письма; прямой `createUser` оставляет `null` |
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
- `API_Auth::authById`, `checkUserAccess` — `role === 'admin'`.

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

## `API_Auth` — пользователи сайта

Наследник `AbstractApi`: `run()` / `handle()`, ответ `status/errorMsg/data/request`,
исключения превращаются в `errorMsg`. Глобальный alias `API_Auth` — для вызова
из блейдов и контроллеров статей.

HTTP: `POST /a_dmin/api/laravelUsers`, `magic.auth` — только админ, для экрана
«Пользователи». Публичного HTTP-входа для посетителей нет: страницы входа и
регистрации — статьи, которые зовут `API_Auth::run()`.

Модель — `App\Models\User` хоста, guard — по умолчанию (`web`).

### Проверки

- `validateEmail`: `trim`, нижний регистр, `email:rfc,dns`, до 255;
- `validatePassword`: `trim`, 8–255;
- `authEmailPassword`: `RateLimiter`, ключ `login:<email>|<ip>`, 5 попыток,
  пауза 60 секунд; после входа `session()->regenerate()`.

### Ключ регистрации

`cryptEmailPass` → `decryptEmailPass`:

```text
json {email, password, date, back}  →  Crypt::encryptString  →  base64url без '='
```

Внутри лежит и пароль (зашифрованный ключом приложения). Срок — `hours`,
по умолчанию 24 часа от `date`. Ключ не одноразовый: пока пароль не сменён,
ссылка снова входит в аккаунт.

### `sendAuthEmail`

1. `blade` и `authPage` непустые, иначе `parameter required: <имя>`;
2. `back` → `localBack()`;
3. `checkGoogleCapture(token)`;
4. email и пароль по правилам выше;
5. пользователь есть — `authEmailPassword`, ответ `back`;
6. нет — `MagicProEvent::addEvent("mail_<email>_registration", +10 минут)`;
   событие уже есть — `registration letter has already been sent`;
7. ключ, рендер `view($blade, ['key', 'back', 'authPage'])`; упал —
   `the letter blade failed to render: <текст>`;
8. письмо `MproHelper::sendMail`, тема — `trim($subject . ' registration letter')`.

`localBack()` пропускает только путь своего сайта: начинается с `/`, не с `//`
и не с `/\`, без управляющих символов. Остальное — `/`. Применяется при
создании ключа, в ответе `sendAuthEmail` и в ответе `processNewUser` (в том
числе для ключей, выпущенных до проверки).

### `processNewUser`

Вошёл на сайт — отказ `user auth`. Иначе расшифровать ключ; пользователь есть —
войти его паролем; нет — `createUser`, `email_verified_at = now()` (поле не в
`fillable` хоста, пишется `update()` по id) и войти. Ответ — `back` из ключа.

### Права в командах

- `editUser`, `chagePassword` — `checkUserAccess`: сам пользователь (`Auth::id()`)
  или админ MagicPro с ролью `admin`;
- `authById`, `deleteUser` — только админ MagicPro;
- `userInfo`, `createUser` — без проверки: решает вызывающий код.

## Первый админ

`magicpro:admin` — только в терминале (`isInteractive()`), email через `ask`,
пароль через `secret`, от `MagicProUser::PASSWORD_MIN` (6) символов, роль `admin`, имя `Admin`. Проверки
email — моделью.
