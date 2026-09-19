# ТЗ v8: регистрация и вход на сайте

Основа — `tzAuth5.md` с ответами владельца. Отличия от v5:

- **три страницы** вместо одной: форма, подтверждение регистрации, смена пароля;
- страница подтверждения сама решает, нужен ли пароль: пользователь уже есть —
  тихо входим, поле пароля не показываем;
- пустой ключ reCAPTCHA — ошибка сервера 500, а не код ошибки;
- названия API, сроки токенов и список `errorCode` согласованы.

## Сценарии

**Email не зарегистрирован.** Форма `authUrl`: ввёл email → «Зарегистрировать?»
→ ушло письмо `authLetter` → переход по ссылке на `registerUrl` → ввод пароля →
регистрация, вход, письмо `postLetter`, переход на `postUrl`.

**Email зарегистрирован.** Форма `authUrl`: ввёл email → ввёл пароль → вход,
переход на `postUrl`.

**Забыл пароль.** Форма `authUrl`: «Забыли пароль?» → ушло письмо
`resetPasswordLetter` → переход по ссылке на `resetPasswordUrl` → ввод нового
пароля → смена пароля, вход, переход на `postUrl`.

Перед любым действием с email — reCAPTCHA: сначала проверяем, что это человек.

## Принцип: без записей в базе

До перехода по ссылке в базе ничего не создаётся. Всё нужное лежит в самом
токене, зашифрованное ключом приложения.

Почему: человек отправил письмо дважды, получил оба и нажал на первое. С таблицей
заявок первое письмо было бы уже недействительным. Здесь любой не просроченный
токен работает, клиента не теряем. Хранить нечего.

| Токен              | Что внутри              | Где живёт                      |
| ------------------ | ----------------------- | ------------------------------ |
| `emailToken`       | email, время создания   | в форме между шагами           |
| токен регистрации  | email, `postUrl`, время | в ссылке `authLetter`          |
| токен смены пароля | email, `postUrl`, время | в ссылке `resetPasswordLetter` |

`postUrl` в токене — только путь этого сайта, как сейчас `back` в `API_Auth`.

## Настройки

Страница «Пользователи» в админке:

| Ключ                     | Что это                                                            |
| ------------------------ | ------------------------------------------------------------------ |
| `authUrl`                | страница формы: вход, регистрация, «забыли пароль»                 |
| `registerUrl`            | страница подтверждения регистрации, сюда ведёт ссылка `authLetter` |
| `resetPasswordUrl`       | страница смены пароля, сюда ведёт ссылка `resetPasswordLetter`     |
| `postUrl`                | куда вести после регистрации или входа                             |
| `authLetter`             | блейд письма подтверждения email                                   |
| `postLetter`             | блейд письма после регистрации                                     |
| `resetPasswordLetter`    | блейд письма смены пароля                                          |
| срок `emailToken`        | в минутах                                                          |
| срок токена регистрации  | в минутах                                                          |
| срок токена смены пароля | в минутах                                                          |

Нет статьи по адресу из настроек — ссылки и переходы туда никуда не ведут. Это
обязанность сайта.

## API

### Два API

- **`API_SiteAuth`** — публичное: шесть команд ниже. HTTP — `POST /api/auth`
  (`routes/site.php`), из блейда — `API_SiteAuth::run()`.
- **`API_Users`** — всё остальное из нынешнего `API_Auth`: список пользователей,
  правка, смена пароля админом, «войти как», удаление, `currentUser`, `logout`
  и т. п. Публичного HTTP нет, из блейда — `API_Users::run()`.
- Удачные решения `API_Auth` переносятся, `API_Auth` удаляется.

Ответ — как в остальных API пакета: `status`, `errorMsg`, `data`, `request`.
При ошибке в `data` — `errorCode`.

### Команды `API_SiteAuth`

| Команда                   | Параметры                          | В `data` при успехе         |
| ------------------------- | ---------------------------------- | --------------------------- |
| `checkEmail`              | `email`, `gToken`                  | `emailToken`, `emailActive` |
| `sendRegisterEmail`       | `emailToken`, `gToken`, `postUrl?` | —                           |
| `auth`                    | `emailToken`, `password`           | `postUrl`                   |
| `sendChangePasswordEmail` | `emailToken`, `gToken`, `postUrl?` | —                           |
| `registerUserByToken`     | `token`, `password?`               | `postUrl`                   |
| `changePassword`          | `token`, `password`                | `postUrl`                   |

- **`checkEmail`** — reCAPTCHA, затем валидность email и есть ли он в базе.
  `emailActive` — есть ли пользователь.
- **`sendRegisterEmail`** — reCAPTCHA; письмо `authLetter`,
  `$authLinkUrl` = `registerUrl` + `/<token>`. Повтор на тот же адрес — не чаще
  раза в 10 минут.
- **`auth`** — вход по email из `emailToken` и паролю; 5 неудач подряд — минута
  паузы.
- **`sendChangePasswordEmail`** — reCAPTCHA; письмо `resetPasswordLetter`,
  `$authResetPasswordUrl` = `resetPasswordUrl` + `/<token>`. Повтор — не чаще
  раза в 10 минут.
- **`registerUserByToken`** — токен жив:
  - пользователь с этим email уже есть — тихо войти, пароль не нужен;
  - пользователя нет, пароль не передан — `errorCode: password_required`;
  - пользователя нет, пароль передан — создать, email подтверждён, войти,
    отправить `postLetter`.
- **`changePassword`** — токен жив: сменить пароль, войти.

Пароль — не короче 8 символов.

**reCAPTCHA.** Ключ `RECAPTCHA_SECRET_KEY` читается как сейчас. Пустой ключ —
ошибка сервера 500: сайт без ключа не должен молча отказывать людям с кодом
«не человек». В доку: перед установкой нового ключа сбросить кеш конфигурации.

### `errorCode`

| Команда                   | Коды                                                                                                                             |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `checkEmail`              | `captcha_failed`, `bad_email`                                                                                                    |
| `sendRegisterEmail`       | `captcha_failed`, `token_invalid`, `token_expired`, `settings_missing`, `letter_already_sent`, `letter_failed`                   |
| `auth`                    | `token_invalid`, `token_expired`, `user_not_found`, `wrong_password`, `too_many_attempts`                                        |
| `sendChangePasswordEmail` | `captcha_failed`, `token_invalid`, `token_expired`, `user_not_found`, `settings_missing`, `letter_already_sent`, `letter_failed` |
| `registerUserByToken`     | `token_invalid`, `token_expired`, `password_required`, `bad_password`, `registration_failed`                                     |
| `changePassword`          | `token_invalid`, `token_expired`, `user_not_found`, `bad_password`                                                               |

`settings_missing` — не заданы `registerUrl`, `resetPasswordUrl` или блейд
письма. `password_required` — новый по сравнению с v5, из-за тихого входа.

## Страницы

Три статьи сайта. Фронт — Alpine.js. Общее — поле пароля с «показать»,
reCAPTCHA, переход на `postUrl` — выносится в include или компонент.

### `authUrl` — форма

Адрес без параметров, маршрут: `getEnable: false`.

email → `checkEmail` → есть: пароль → `auth`; нет: «Зарегистрировать?» →
`sendRegisterEmail`. «Забыли пароль?» → `sendChangePasswordEmail`.

### `registerUrl` — подтверждение регистрации

Адрес `/<registerUrl>/<token>`, маршрут: `getEnable: true`, `bindKeys: true`,
`keysArr: ["token"]`.

Порядок в блейде:

1. Посетитель уже вошёл — переход на `postUrl`.
2. Не вошёл — `registerUserByToken(token)` без пароля:
   - успех (пользователь уже был, тихо вошёл) — переход на `postUrl`;
   - `password_required` — показать поле пароля → `registerUserByToken(token,
password)` → переход на `postUrl`;
   - `token_expired` — форма входа и регистрации `authUrl`: зарегистрирован
     человек — войдёт паролем, нет — зарегистрируется заново.

### `resetPasswordUrl` — смена пароля

Адрес `/<resetPasswordUrl>/<token>`, маршрут: `getEnable: true`,
`bindKeys: true`, `keysArr: ["token"]`.

Поле нового пароля → `changePassword(token, password)` → переход на `postUrl`.
`token_expired` — форма `authUrl`.

## Письма

Блейды писем — статьи сайта, их имена в настройках. Переменные подставляет API:

| Письмо                | Переменные                    |
| --------------------- | ----------------------------- |
| `authLetter`          | `$authLinkUrl`                |
| `postLetter`          | `$userEmail`, `$userPassword` |
| `resetPasswordLetter` | `$authResetPasswordUrl`       |

`$userPassword` передаётся, но выводить его сайт не обязан: можно написать
«пароль, указанный при регистрации».

Примеры — `authLetter.md`.

## Статьи на тестовом сайте

Раздел `Auth` (id 239) зовёт `sendAuthEmail` и `processNewUser` — делаются
заново под новое API.

## Что есть в пакете сейчас (сверено с кодом `API_Auth`)

| Нужно                                                                                              | Есть                                                                                                                              |
| -------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| шифрованный токен без базы                                                                         | `cryptEmailPass` / `decryptEmailPass`: email, пароль, время, `back`; `Crypt::encryptString`, base64url; срок 24 часа по умолчанию |
| проверка «только путь своего сайта»                                                                | `localBack()`                                                                                                                     |
| регистрация по токену, email подтверждён, вход                                                     | `processNewUser`                                                                                                                  |
| письмо регистрации                                                                                 | `sendAuthEmail` — вход и письмо в одной команде, с паролем в токене                                                               |
| ограничение повтора письма 10 минут                                                                | `MagicProEvent::addEvent("mail_<email>_registration")`                                                                            |
| вход, 5 неудач — минута паузы                                                                      | `authEmailPassword`                                                                                                               |
| reCAPTCHA                                                                                          | `checkGoogleCapture`                                                                                                              |
| «есть ли email»                                                                                    | `userInfo`                                                                                                                        |
| `emailToken`, смена пароля по ссылке, `postLetter`, настройки, сроки токенов, публичный HTTP-адрес | нет                                                                                                                               |

## Позже

- Форма модалкой на любой странице: `<x-magic::register_form postUrl="…" />` —
  регистрация без ухода со страницы.
- Примеры к дистрибутиву — JSON-файлы импорта: регистрация и другое.

## Вопросы

1. **`registerUrl`, шаг 1: посетитель уже вошёл** — переход на `postUrl`, как
   записано? А если вошёл другой пользователь, не тот, чей email в токене?

   > Если юзер авторизовался под емалй1 а токен под емайл2 сообщение. Вы уже авторизованы. Если под email1 ничего не сообщать просто переадресация.

2. **`resetPasswordUrl`: посетитель уже вошёл** — страница всё равно меняет
   пароль по токену, или переводит на `postUrl`?

3. **Умолчания сроков токенов** в минутах: например,

   > `emailToken` — 30,
   > регистрация — 1440 (сутки),
   > смена пароля — 120

4. **`authUrl` при протухшем токене** — просто переход на форму, или с
   подставленным email и сообщением «ссылка устарела»?
   > ссылка устарела, прислать новую?
   > Да
   > Идет обновленная ссылка
   > нам важен каждый клиент, даже тупой.
