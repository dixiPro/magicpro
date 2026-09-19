# ТЗ v9: регистрация и вход на сайте

Основа — `tzAuth8.md` с ответами владельца. Отличия от v8:

- **протухшая ссылка** — «Ссылка устарела, прислать новую?» → да → уходит
  свежее письмо. Нам важен каждый клиент;
- **уже вошёл на странице подтверждения**: тот же email — молча переходим на
  `postUrl`, другой email — сообщение «Вы уже авторизованы»;
- умолчания сроков токенов.

## Сценарии

**Email не зарегистрирован.** Форма `authUrl`: ввёл email → «Зарегистрировать?»
→ ушло письмо `authLetter` → переход по ссылке на `registerUrl` → ввод пароля →
регистрация, вход, письмо `postLetter`, переход на `postUrl`.

**Email зарегистрирован.** Форма `authUrl`: ввёл email → ввёл пароль → вход,
переход на `postUrl`.

**Забыл пароль.** Форма `authUrl`: «Забыли пароль?» → ушло письмо
`resetPasswordLetter` → переход по ссылке на `resetPasswordUrl` → ввод нового
пароля → смена пароля, вход, переход на `postUrl`.

**Ссылка протухла.** Страница `registerUrl` или `resetPasswordUrl`: «Ссылка
устарела, прислать новую?» → да → на тот же email уходит новое письмо того же
вида.

Перед любым действием с email — reCAPTCHA: сначала проверяем, что это человек.

## Принцип: без записей в базе

До перехода по ссылке в базе ничего не создаётся. Всё нужное лежит в самом
токене, зашифрованное ключом приложения.

Почему: человек отправил письмо дважды, получил оба и нажал на первое. С таблицей
заявок первое письмо было бы уже недействительным. Здесь любой не просроченный
токен работает, клиента не теряем. Хранить нечего.

| Токен              | Что внутри              | Где живёт                      | Срок по умолчанию  |
| ------------------ | ----------------------- | ------------------------------ | ------------------ |
| `emailToken`       | email, время создания   | в форме между шагами           | 30 минут           |
| токен регистрации  | email, `postUrl`, время | в ссылке `authLetter`          | 1440 минут (сутки) |
| токен смены пароля | email, `postUrl`, время | в ссылке `resetPasswordLetter` | 120 минут          |

`postUrl` в токене — только путь этого сайта, как сейчас `back` в `API_Auth`.

Протухший токен по-прежнему расшифровывается: срок проверяется по времени
внутри. Поэтому по протухшей ссылке известен email, и новое письмо можно
отправить без повторного ввода.

## Настройки

Страница «Пользователи» в админке:

| Ключ                     | Что это                                                            | Умолчание |
| ------------------------ | ------------------------------------------------------------------ | --------- |
| `authUrl`                | страница формы: вход, регистрация, «забыли пароль»                 | —         |
| `registerUrl`            | страница подтверждения регистрации, сюда ведёт ссылка `authLetter` | —         |
| `resetPasswordUrl`       | страница смены пароля, сюда ведёт ссылка `resetPasswordLetter`     | —         |
| `postUrl`                | куда вести после регистрации или входа                             | —         |
| `authLetter`             | блейд письма подтверждения email                                   | —         |
| `postLetter`             | блейд письма после регистрации                                     | —         |
| `resetPasswordLetter`    | блейд письма смены пароля                                          | —         |
| срок `emailToken`        | минуты                                                             | 30        |
| срок токена регистрации  | минуты                                                             | 1440      |
| срок токена смены пароля | минуты                                                             | 120       |

Нет статьи по адресу из настроек — ссылки и переходы туда никуда не ведут. Это
обязанность сайта.

## API

### Два API

- **`API_SiteAuth`** — публичное: команды ниже. HTTP — `POST /api/auth`
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
| `renewLink`               | `token`, `gToken`                  | —                           |

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
- **`registerUserByToken`**:
  - посетитель уже вошёл под email из токена — успех, пароль не нужен;
  - посетитель вошёл под другим email — `errorCode: logged_in_as_other`;
  - токен протух — `errorCode: token_expired`;
  - пользователь с этим email уже есть — тихо войти, успех;
  - пользователя нет, пароль не передан — `errorCode: password_required`;
  - пользователя нет, пароль передан — создать, email подтверждён, войти,
    отправить `postLetter`, успех.
- **`changePassword`** — токен жив: сменить пароль, войти; протух —
  `token_expired`.
- **`renewLink`** — по протухшему токену регистрации или смены пароля: reCAPTCHA;
  на email из токена уходит новое письмо того же вида с новым токеном, `postUrl`
  переносится. Повтор — не чаще раза в 10 минут.

Пароль — не короче 8 символов.

**reCAPTCHA.** Ключ `RECAPTCHA_SECRET_KEY` читается как сейчас. Пустой ключ —
ошибка сервера 500. В доку: перед установкой нового ключа сбросить кеш
конфигурации.

### `errorCode`

| Команда                   | Коды                                                                                                                             |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `checkEmail`              | `captcha_failed`, `bad_email`                                                                                                    |
| `sendRegisterEmail`       | `captcha_failed`, `token_invalid`, `token_expired`, `settings_missing`, `letter_already_sent`, `letter_failed`                   |
| `auth`                    | `token_invalid`, `token_expired`, `user_not_found`, `wrong_password`, `too_many_attempts`                                        |
| `sendChangePasswordEmail` | `captcha_failed`, `token_invalid`, `token_expired`, `user_not_found`, `settings_missing`, `letter_already_sent`, `letter_failed` |
| `registerUserByToken`     | `token_invalid`, `token_expired`, `logged_in_as_other`, `password_required`, `bad_password`, `registration_failed`               |
| `changePassword`          | `token_invalid`, `token_expired`, `user_not_found`, `bad_password`                                                               |
| `renewLink`               | `captcha_failed`, `token_invalid`, `settings_missing`, `letter_already_sent`, `letter_failed`                                    |

`settings_missing` — не заданы `registerUrl`, `resetPasswordUrl` или блейд
письма. `token_invalid` — токен битый, не расшифровывается; такому новое письмо
не отправить.

## Страницы

Три статьи сайта. Фронт — Alpine.js. Общее — поле пароля с «показать»,
reCAPTCHA, переход на `postUrl`, «Ссылка устарела, прислать новую?» —
выносится в include или компонент.

### `authUrl` — форма

Адрес без параметров, маршрут: `getEnable: false`.

email → `checkEmail` → есть: пароль → `auth`; нет: «Зарегистрировать?» →
`sendRegisterEmail`. «Забыли пароль?» → `sendChangePasswordEmail`.

### `registerUrl` — подтверждение регистрации

Адрес `/<registerUrl>/<token>`, маршрут: `getEnable: true`, `bindKeys: true`,
`keysArr: ["token"]`.

Блейд зовёт `registerUserByToken(token)` без пароля:

| Ответ                | Что делает страница                                                         |
| -------------------- | --------------------------------------------------------------------------- |
| успех                | переход на `postUrl`                                                        |
| `logged_in_as_other` | сообщение «Вы уже авторизованы»                                             |
| `password_required`  | поле пароля → `registerUserByToken(token, password)` → переход на `postUrl` |
| `token_expired`      | «Ссылка устарела, прислать новую?» → да → `renewLink` → «Письмо отправлено» |
| `token_invalid`      | «Ссылка неверная» и форма `authUrl`                                         |

### `resetPasswordUrl` — смена пароля

Адрес `/<resetPasswordUrl>/<token>`, маршрут: `getEnable: true`,
`bindKeys: true`, `keysArr: ["token"]`.

Поле нового пароля → `changePassword(token, password)` → переход на `postUrl`.
`token_expired` — «Ссылка устарела, прислать новую?» → `renewLink`.

## Письма

Блейды писем — статьи сайта, их имена в настройках. Переменные подставляет API:

| Письмо                | Переменные                    |
| --------------------- | ----------------------------- |
| `authLetter`          | `$authLinkUrl`                |
| `postLetter`          | `$userEmail`, `$userPassword` |
| `resetPasswordLetter` | `$authResetPasswordUrl`       |

`$userPassword` передаётся, но выводить его сайт не обязан: можно написать
«пароль, указанный при регистрации».

Письмо по `renewLink` — тот же блейд, что и первое письмо этого вида.

Примеры — `authLetter.md`.

## Статьи на тестовом сайте

Раздел `Auth` (id 239) зовёт `sendAuthEmail` и `processNewUser` — делаются
заново под новое API.

## Что есть в пакете сейчас (сверено с кодом `API_Auth`)

| Нужно                                                                                                                                | Есть                                                                                                                              |
| ------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------- |
| шифрованный токен без базы                                                                                                           | `cryptEmailPass` / `decryptEmailPass`: email, пароль, время, `back`; `Crypt::encryptString`, base64url; срок 24 часа по умолчанию |
| проверка «только путь своего сайта»                                                                                                  | `localBack()`                                                                                                                     |
| регистрация по токену, email подтверждён, вход                                                                                       | `processNewUser`                                                                                                                  |
| письмо регистрации                                                                                                                   | `sendAuthEmail` — вход и письмо в одной команде, с паролем в токене                                                               |
| ограничение повтора письма 10 минут                                                                                                  | `MagicProEvent::addEvent("mail_<email>_registration")`                                                                            |
| вход, 5 неудач — минута паузы                                                                                                        | `authEmailPassword`                                                                                                               |
| reCAPTCHA                                                                                                                            | `checkGoogleCapture`                                                                                                              |
| «есть ли email»                                                                                                                      | `userInfo`                                                                                                                        |
| `emailToken`, смена пароля по ссылке, `postLetter`, новое письмо по протухшей ссылке, настройки, сроки токенов, публичный HTTP-адрес | нет                                                                                                                               |

Сейчас `decryptEmailPass` превращает «срок вышел» в общее «Auth key invalid» —
для `token_expired` и `renewLink` протухший токен нужно отличать от битого.

## Позже

- Форма модалкой на любой странице: `<x-magic::register_form postUrl="…" />` —
  регистрация без ухода со страницы.
- Примеры к дистрибутиву — JSON-файлы импорта: регистрация и другое.

## Вопросы

1. **`resetPasswordUrl`: посетитель уже вошёл** — в v8 без ответа. Менять
   пароль по токену всё равно, или переводить на `postUrl`? И если вошёл под
   другим email — тоже «Вы уже авторизованы»?
   > Ничего не менять, можно будет это дорешать на странице.
2. **`renewLink`** — отдельная команда, как записано, или новое письмо по
   протухшей ссылке делают `sendRegisterEmail` / `sendChangePasswordEmail`,
   если им передать протухший токен?
   > Новый токен, опять таки можно будет дорешать это на странице.
3. **`logged_in_as_other`** — только сообщение, или ещё кнопка «Выйти и
   продолжить регистрацию»?
   > Только сообщение, опять таки можно будет дорешать это на странице.
