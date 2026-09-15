# Тесты: незакрытое

Автотестов нет ни у одной подсистемы.

Багов: 4. Список всех подсистем — `toDo/risks.md`.

---

## 1. Нет актуального набора AWS-тестов

Статус: **покрытие**.

Поиск в `src/test` и `tests` не нашёл тестов для:

- `SetupCommand`;
- `StatusCommand`;
- `Subscriptions`;
- `SnsSignature`;
- `AwsHookHandler`.

Найдены тесты почтового API, но они не закрывают AWS setup, signature verification и webhook
state machine. Для кода, который ротирует credentials и принимает публичные события, это
существенный пробел.

Рекомендуемая матрица тестов приведена в `inside-codex.md`.

_Было: AWS 29._

## 2. Для cron-подсистемы нет выделенных автоматических тестов

В пакете не найдено тестов для `API_Cron`, `CronTaskChecker`,
`CronTaskRunner`, динамической регистрации, mutex и heartbeat. Ошибки на
границах ручного и автоматического путей сейчас обнаруживаются только чтением
кода или ручной проверкой.

_Было: крон 21._

## 3. Автоматических тестов import/snapshot не найдено

Статус: **покрытие**.

В `src/test` найдены только mail tests и вспомогательный user seeder. Тестов для следующих
контрактов нет:

- parser/order/report codes;
- partial/full apply;
- collision subtree;
- root semantics;
- DB rollback и post-commit generation failure;
- snapshot naming/overwrite/I/O;
- restore/delete/download;
- frontend readiness и mode change;
- authorization/CSRF;
- archive/cron interactions.

Из-за destructive поведения и двухсистемной границы DB/filesystem отсутствие failure-injection
tests является существенным риском.

_Было: импорт 35._

## 4. Нет специальных автоматических тестов MCP

В `src/test` не найдены тесты server registry, tool schemas, токенов,
документации, HTTP middleware или двухфазных удалений. Именно в этих местах уже
есть расхождения между Description и кодом.

При исправлении нельзя ограничиваться happy path. Минимальная матрица приведена
в `inside-codex.md`, раздел «Что проверить после правок».

_Было: МСП 20._
