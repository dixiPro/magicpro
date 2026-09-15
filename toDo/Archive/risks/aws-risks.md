# AWS: незакрытые пункты

Из двадцати девяти находок разбора закрыто восемнадцать: ротация ключа, ложный
успех команды, призрачный `.env_mpro`, поздняя запись секрета, чужой топик,
кеш сертификата, повторы и старьё, разные правила хоста, `AdministratorAccess`,
срок подписки, шаблон `MAIL_FROM_*`, порт SMTP, проверка ответа на подтверждение
подписки, откат статуса письма и аудит IAM в `status`.

Здесь одиннадцать оставшихся. Порядок — по тому, что я считаю важным.

| № | Чем аукнется |
| --- | --- |
| 4 | `config:cache` — и `env()` возвращает пусто: SES API и проверка топика молча выключаются |
| 27 | повторный setup перезаписывает политику пользователя, если её правили руками |
| 9 | файл результата пишется без атомарности и без проверки, что он уже есть |
| 15 | существующий пользователь не приводится к заявленным тегам |
| 16 | одно имя `user` идёт в несовместимые пространства имён без ранней проверки |
| 17 | настраиваются восемь типов событий, четыре из них никто не обрабатывает |
| 10 | `aws-status` отдаёт успех даже когда секции упали |
| 14 | список identities в status не пагинируется |
| 26 | `gitIgnore()` меняет файл вне зоны ответственности команды |
| 28 | status не проверяет сквозную доставку письма |
| 29 | у подсистемы AWS нет актуальных тестов |

Первый — самый неприятный: он не виден никак. `php artisan config:cache` на
боевом, и `env('AWS_SesV2Client')` вернёт пусто, письма молча поедут через SMTP
вместо API, а проверка топика в вебхуке выключится. Лечится переносом этих
значений в конфиг пакета, но это отдельная работа: у MagicPro своего конфига
сейчас нет вовсе.

---

### 4. Прямые `env()` ломают выбор SES API и configuration set при `config:cache`

Статус: **ошибка интеграции**.

Где:

- `src/Mail/API_Mail.php`;
- связанные mail-классы, добавляющие `X-SES-CONFIGURATION-SET`;
- `src/Console/Aws/StatusCommand.php`.

`AWS_SesV2Client` и `AWS_SES_CONFIGURATION_SET` читаются через `env()` непосредственно во время
работы приложения. После `php artisan config:cache` Laravel не загружает `.env`; `env()` вне
config-файлов видит только внешние переменные процесса.

Влияние:

- приложение может перейти с SES API на SMTP не по намерению оператора;
- configuration set может перестать передаваться;
- SES events не будут связаны с отправкой;
- status способен показать false/пустое значение при наличии строки в `.env`.

Желаемое направление: объявить оба значения в config-файле пакета/приложения и использовать
`config()` во всём runtime-коде.

Источник: [Laravel 13: Configuration Caching](https://laravel.com/framework/docs/13.x/configuration#configuration-caching).

## Высокий приоритет

### 9. Файл результата создаётся и перезаписывается небезопасно

Статус: **риск безопасности и потери данных**.

Где:

- `src/Console/Aws/SetupCommand.php`, `writeResult()`;
- `src/Console/Aws/AwsCommand.php`, `gitIgnore()`.

Наблюдаемое поведение:

- `file_put_contents()` вызывается без `LOCK_EX` и без exclusive create;
- существующий `--out` перезаписывается без подтверждения;
- return value записи не проверяется;
- права `0600` назначаются после создания файла;
- ошибка `chmod` подавляется через `@` и не проверяется;
- обновление `.gitignore` также не проверяет результат записи.

Файл содержит IAM secret key и SMTP password. На окружении с permissive umask возникает окно,
когда новый файл может иметь более широкие права. При сбое записи команда способна вывести
сообщение об успехе.

Желаемое направление: безопасное создание нового файла с правами владельца, проверка всех
операций, отказ от молчаливого overwrite, атомарное перемещение и ясная очистка secret-файла.

### 10. `magicpro:aws-status` возвращает успех при ошибках секций

Статус: **ошибка**.

Где:

- `src/Console/Aws/StatusCommand.php`, `handle()`.

Каждая секция имеет собственный `catch (Throwable)`, но итог всегда `self::SUCCESS`. Команда с
недоступным SES, отсутствующими permissions или частично сломанной конфигурацией выглядит
успешной для автоматизации.

Желаемое направление: продолжать сбор остальных секций, но запоминать наличие ошибок и
возвращать ненулевой exit code в конце.

### 14. Список SES identities в status не пагинируется

Статус: **ошибка диагностики**.

Где:

- `src/Console/Aws/StatusCommand.php`.

`ListEmailIdentities()` вызывается ровно один раз, `NextToken` не обрабатывается. AWS SDK
описывает операцию как пагинируемую. В аккаунте с большим числом identities вывод будет
неполным без предупреждения.

Для сравнения, поиск SNS topic и список subscriptions пагинацию обрабатывают.

### 15. Существующий IAM user не приводится к заявленным tags

Статус: **расхождение поведения**.

Где:

- `src/Console/Aws/SetupCommand.php`, `user()`;
- `docs/ru/aws/use.md`.

Tags `magicpro`, `domain`, `created` добавляются только в `CreateUser`. Если user уже существует,
его tags не читаются и не обновляются. Поэтому повторный setup с другим domain не приводит
ресурс к описанной форме, хотя inline policy обновляется.

Нужно решить контракт явно: tags принадлежат setup и должны reconciliate, либо они являются
только метаданными момента создания.

### 16. Одно значение `user` используется для несовместимых namespaces без ранней проверки

Статус: **риск частичной настройки**.

Где:

- `src/Console/Aws/AwsCommand.php`, `names()`;
- `src/Console/Aws/SetupCommand.php`.

`user` становится IAM username, SNS topic prefix, SES configuration set и частью destination
name. Команда проверяет лишь непустую строку. Правила имён сервисов различаются. Значение может
быть допустимо для IAM, после чего setup создаст/изменит IAM, но упадёт при создании SNS/SES.

Например, SNS topic name разрешает только ASCII letters, digits, hyphen и underscore и имеет
ограничение длины. Дополнительный suffix `-events` тоже занимает длину.

Желаемое направление: до первого mutation валидировать производные имена по пересечению правил
всех сервисов и показывать каждое итоговое имя.

Источник: [SNS CreateTopic API](https://docs.aws.amazon.com/sns/latest/api/API_CreateTopic.html).

### 17. Настраиваются восемь event types, но четыре из них игнорируются

Статус: **расхождение функциональности / лишняя нагрузка**.

Где:

- `src/Console/Aws/SetupCommand.php`, event destination;
- `src/Mail/AwsHookHandler.php`, `applyEvent()`.

SES отправляет:

```text
SEND, DELIVERY, BOUNCE, COMPLAINT, OPEN, CLICK, REJECT, RENDERING_FAILURE
```

Прикладное действие есть только для Delivery, Open, Bounce и Complaint. Send, Click, Reject и
Rendering Failure успешно принимаются, но не сохраняются и не меняют статус. Общий лог SNS не
содержит их SES payload/event type.

Нужно либо реализовать контракт этих событий, либо не подписываться на ненужные типы, либо
явно считать их наблюдаемыми telemetry events и сохранять соответствующие данные.

### 26. `gitIgnore()` создаёт побочное изменение вне AWS resources

Статус: **неявный побочный эффект**.

Setup автоматически дописывает `*.result` в корневой `.gitignore`. Это полезная защита, но:

- изменение не запрашивается отдельно;
- ошибка записи игнорируется;
- pattern глобально скрывает все `*.result`, не только AWS secrets;
- команда с AWS-задачей меняет рабочее дерево приложения.

Поведение нужно явно документировать либо заменить безопасным заранее подготовленным правилом.

### 27. Повторный setup перезаписывает существующие policies

Статус: **риск управления ресурсами**.

`PutUserPolicy` заменяет inline policy с вычисленным именем, а `SetTopicAttributes` заменяет
topic policy целиком. Ручные statements в этих именованных policies исчезнут без diff и
подтверждения.

Это может быть осознанной reconciliation-моделью, но тогда ownership этих ресурсов должен быть
явным: ими полностью владеет MagicPro, ручные изменения не поддерживаются.

### 28. Status не проверяет end-to-end доставку

Статус: **ограничение диагностики**.

Зелёные строки status не доказывают, что:

- API или SMTP credentials действуют;
- FROM разрешён;
- configuration set реально передаётся отправителем;
- topic policy разрешает publish;
- webhook принимает событие нужного topic;
- `provider_message_id` сохраняется и находится;
- очередь использует ожидаемый transport.

Нужен отдельный opt-in smoke test с явным адресом и предупреждением о реальной отправке; status
следует оставлять read-only.

### 29. Нет актуального набора AWS-тестов

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

## Приоритет исправления

Предлагаемый порядок, без смешивания в один большой change:

1. Сделать ротацию access key безопасной и исправить exit codes setup/status.
2. Добавить allowlist ожидаемого `TopicArn` и тесты signature/handler.
3. Перенести runtime `env()` в config-контракт и проверить config cache.
4. Сделать немедленное безопасное сохранение нового secret и надёжный result writer.
5. Исправить `.env_mpro`/root `.env` контракт и опечатку региона.
6. Проверять confirmation HTTP response и отрицательный cache сертификатов.
7. Добавить IAM audit, pagination и раннюю валидацию всех resource names.
8. Определить state machine событий, replay policy и нужный набор event types.
9. Заменить AdministratorAccess на документированную least-privilege схему.
10. После каждого изменения обновлять пользовательскую и внутреннюю документацию вместе с
    тестами.

## Проверенные официальные источники

- [IAM access keys](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html)
- [IAM best practices](https://docs.aws.amazon.com/IAM/latest/UserGuide/best-practices.html)
- [SES SMTP credentials](https://docs.aws.amazon.com/ses/latest/dg/smtp-credentials.html)
- [SES regions](https://docs.aws.amazon.com/ses/latest/dg/regions.html)
- [SNS signature verification](https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message.html)
- [SNS signature version](https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message-configure-message-signature.html)
- [SNS pending subscription lifetime](https://docs.aws.amazon.com/sns/latest/dg/sns-delete-subscription-topic.html)
- [SNS topic naming contract](https://docs.aws.amazon.com/sns/latest/api/API_CreateTopic.html)
- [Laravel configuration caching](https://laravel.com/framework/docs/13.x/configuration#configuration-caching)
