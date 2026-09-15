# AWS: найденные ошибки и риски

Этот файл фиксирует проблемы, обнаруженные при сопоставлении документации, текущего PHP-кода,
конфигурации хост-приложения и официальных контрактов AWS/Laravel. Исходный код и существующие
страницы не исправлялись.

Статусы:

- **ошибка** — текущее поведение неверно или расходится с заявленным контрактом;
- **риск** — код работает в обычном сценарии, но небезопасен при отказе или особом окружении;
- **документация** — существующее описание не соответствует реализации или внешнему контракту;
- **покрытие** — важное поведение не защищено автоматическими тестами.

## Критические

### 1. Ротация может оставить IAM user без активного ключа

Статус: **ошибка**.

Где:

- `src/Console/Aws/SetupCommand.php`, метод выпуска access key.

Текущее поведение:

1. `ListAccessKeys` получает ключи пользователя.
2. Все активные ключи переводятся в `Inactive`.
3. Только после этого вызывается `CreateAccessKey`.
4. Неактивные старые ключи никогда не удаляются.

AWS разрешает не больше двух access keys на IAM user, включая неактивные. Если оба слота уже
заняты, `CreateAccessKey` завершится ошибкой после деактивации рабочего ключа. У приложения не
останется активных credentials. Даже при одном ключе алгоритм намеренно создаёт перерыв между
его отключением и установкой нового в приложение.

Дополнительная проблема: исключение будет поймано общим `catch`, но команда может вернуть
успешный exit code — см. следующий пункт.

Желаемое направление:

- валидировать число и статусы ключей до изменения;
- создавать новый ключ при свободном слоте до отключения старого;
- переключать и проверять приложение;
- только затем деактивировать/удалять прежний ключ;
- иметь явный recovery plan при частичном отказе.

Источник: [официальное ограничение IAM access keys](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html).

### 2. `magicpro:aws-setup` возвращает успех после большинства ошибок

Статус: **ошибка**.

Где:

- `src/Console/Aws/SetupCommand.php`, `handle()`.

Основные AWS-операции обёрнуты в `catch (Throwable)`. Catch печатает сообщение, но выполнение
продолжается к notes/result и в конце возвращается `self::SUCCESS`.

Исключение: неготовый domain/DKIM возвращает `FAILURE` напрямую. Ошибки IAM, SNS, configuration
set, subscription и многие локальные ошибки могут завершиться exit code `0`.

Влияние:

- shell/CI считает частичную настройку успешной;
- оператор может не заметить ошибку среди вывода;
- после сбоя ротации автоматизация способна продолжить deployment с нерабочей почтой;
- rollback отсутствует, поэтому AWS остаётся в частично изменённом состоянии.

Желаемое направление: накапливать явный результат этапов и возвращать `FAILURE` при любом
необработанном или критическом отказе; отдельно показывать, какие mutations успели выполниться.

### 3. Webhook принимает подписанные сообщения из любого SNS topic

Статус: **ошибка безопасности**.

Где:

- `src/Mail/AwsHookHandler.php`;
- `src/Mail/SnsSignature.php`.

Криптографическая подпись проверяется, но `TopicArn` не сравнивается с ожидаемым topic/account/
region. Валидное сообщение, подписанное SNS для чужого topic, проходит проверку. Это относится и
к `SubscriptionConfirmation`, и к `Notification`.

AWS отдельно рекомендует проверять, что `TopicArn` соответствует ожидаемому topic. Без allowlist
владелец другого AWS topic может направить на публичный endpoint корректно подписанные
сообщения. Для изменения конкретной записи дополнительно потребуется угадать/узнать
`provider_message_id`, но сама граница доверия всё равно нарушена.

Желаемое направление:

- хранить ожидаемый полный topic ARN в config;
- после проверки подписи сравнивать ARN точным сравнением;
- при необходимости отдельно фиксировать account и region;
- отклонять confirmation и notification до любых исходящих запросов и изменений данных.

Источник: [AWS: Verify the signature of Amazon SNS messages](https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message.html).

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

### 5. Документация направляет секреты в `.env_mpro`, который не загружается найденным кодом

Статус: **документация / конфигурация**.

Где:

- `docs/ru/aws/use.md`;
- console output `magicpro:aws-setup`;
- `packages/dixipro/magicpro/.env_mpro`.

Существующая инструкция говорит скопировать результат в `.env_mpro`. При поиске по пакету,
bootstrap и config хост-приложения не найден загрузчик package `.env_mpro`. Стандартный Laravel
host читает корневой `.env`; `config/mail.php` и `config/services.php` берут значения оттуда.

Возможна внешняя deployment-процедура, не хранящаяся в репозитории, которая объединяет этот
файл. Внутри проверенного кода такого контракта нет.

Влияние: пользователь следует инструкции, но приложение продолжает работать со старыми или
пустыми credentials.

Желаемое направление: либо реализовать и документировать загрузку `.env_mpro`, либо явно
указывать фактический root `.env`/system environment/secret manager.

### 6. В примере `.env_mpro` опечатка `cAWS_DEFAULT_REGION`

Статус: **ошибка документации / шаблона**.

Где:

- `packages/dixipro/magicpro/.env_mpro`.

Вместо ожидаемого:

```dotenv
AWS_DEFAULT_REGION=...
```

записано:

```dotenv
cAWS_DEFAULT_REGION=xxx
```

`config/services.php` читает `AWS_DEFAULT_REGION`, поэтому копирование строки не задаёт регион.

### 7. Secret нового project key сохраняется слишком поздно

Статус: **риск отказа**.

Где:

- `src/Console/Aws/SetupCommand.php`, порядок `handle()` и `writeResult()`.

После `CreateAccessKey` secret key существует только в памяти процесса. Затем команда ещё
вычисляет SMTP credentials, меняет SNS/SES resources, проверяет webhook и ждёт subscription.
Файл результата пишется только после завершения общего try/catch.

Если процесс аварийно завершится, будет прерван пользователем или упадёт вне catch до записи,
новый secret восстановить нельзя. Старый активный ключ к этому моменту уже деактивирован.

Желаемое направление: сделать выпуск/сохранение атомарным насколько возможно, немедленно
зафиксировать secret безопасным способом, а дальнейшие этапы отделить от необратимой ротации.

### 8. Успех HTTP GET подтверждения подписки не проверяется

Статус: **ошибка**.

Где:

- `src/Mail/AwsHookHandler.php`, обработка `SubscriptionConfirmation`.

После валидации `SubscribeURL` выполняется `HTTP GET`, но response status и body не проверяются.
Если сервер AWS вернул 4xx/5xx без transport exception, код всё равно пишет в журнал, что
подписка подтверждена, и отвечает `status=true`.

Влияние: ложный успешный лог затрудняет диагностику; подписка остаётся pending.

Желаемое направление: требовать успешный HTTP status, логировать status без чувствительных
данных и возвращать контролируемую ошибку/повторяемый ответ.

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

### 11. `magicpro:aws-status` не проверяет IAM, хотя документация обещает общую картину

Статус: **документация / неполная диагностика**.

Где:

- `docs/ru/aws/use.md`;
- `src/Console/Aws/StatusCommand.php`.

Status показывает SES identity/account, SNS topic/subscriptions, configuration set destinations
и часть Laravel mail config. Он не вызывает IAM и не показывает:

- существует ли project user;
- его tags;
- inline policy;
- число и статусы access keys;
- возраст ключей.

Именно эти данные критичны перед текущей ротацией. Формулировки вроде «как всё настроено»
создают ложное ощущение полноты.

Желаемое направление: либо сузить обещание документации, либо добавить read-only IAM audit.

## Средний приоритет

### 12. Неверно сказано, что IAM access key выпускается для одного региона

Статус: **документация / комментарий кода**.

Где:

- `src/Console/Aws/SetupCommand.php`, текст перед выпуском ключа;
- `docs/ru/aws/use.md`.

IAM access key не является региональным. Регионально зависим SES SMTP password, который
вычисляется из IAM secret access key и region. Одинаковые SMTP credentials нельзя переносить
между регионами, но исходный IAM key может подписывать разрешённые AWS API requests в разных
регионах.

Источники:

- [Obtaining Amazon SES SMTP credentials](https://docs.aws.amazon.com/ses/latest/dg/smtp-credentials.html)
- [Amazon SES regions and endpoints](https://docs.aws.amazon.com/ses/latest/dg/regions.html)

### 13. Срок pending subscription указан как три дня вместо 48 часов

Статус: **документация / комментарий кода**.

Где:

- `docs/ru/aws/use.md`;
- сообщения и комментарии AWS setup/subscription.

Официальная документация SNS указывает автоматическое удаление неподтверждённой подписки через
48 часов. Три дня — неверный срок.

Источник: [Deleting an Amazon SNS subscription and topic](https://docs.aws.amazon.com/sns/latest/dg/sns-delete-subscription-topic.html).

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

### 18. Допустимые AWS URL различаются для certificate и confirmation

Статус: **ошибка совместимости**.

Где:

- `src/Mail/SnsSignature.php`, `certificateUrl()` и `amazonUrl()`.

Certificate URL разрешает SNS hosts с окончанием `.amazonaws.com.cn`. Confirmation URL
разрешает только `amazonaws.com` и `.amazonaws.com`. Поэтому сообщение из China region может
пройти signature certificate validation, но его `SubscribeURL` будет отвергнут.

Кроме того, confirmation host-проверка шире SNS-specific pattern: она допускает любой host под
`.amazonaws.com`. Подпись защищает значение URL, но строгий SNS allowlist был бы яснее и
симметричнее.

### 19. Неуспешная загрузка сертификата может кэшировать пустую строку на сутки

Статус: **риск доступности**.

Где:

- `src/Mail/SnsSignature.php`, получение сертификата через `Cache::remember()`.

Callback возвращает пустую строку при неуспешном HTTP response. В зависимости от cache driver
это значение сохраняется на 86400 секунд. Временный 5xx способен привести к длительному отказу
проверки сообщений для того же `SigningCertURL` даже после восстановления AWS endpoint.

Желаемое направление: не кэшировать отрицательный результат либо давать ему короткий отдельный
TTL; логировать причину без содержимого сертификата.

### 20. Не проверяется свежесть SNS Timestamp и повтор MessageId

Статус: **риск replay**.

Где:

- `src/Mail/SnsSignature.php`;
- `src/Mail/AwsHookHandler.php`.

Подпись подтверждает целостность, но старое подписанное сообщение остаётся валидным. Нет окна
времени по `Timestamp` и хранилища уже обработанных `MessageId`.

Текущие операции в основном идемпотентны лишь частично. Повтор Delivery/Open меняет timestamp и
может влиять на порядок статусов; повтор confirmation снова выполняет GET.

Нужно определить допустимое окно с учётом задержек SNS и стратегию дедупликации, прежде чем
добавлять жёсткое отклонение.

### 21. Состояние письма может регрессировать с `open` до `delivered`

Статус: **ошибка модели событий**.

Где:

- `src/Mail/AwsHookHandler.php`.

Open имеет десятисекундную защиту от слишком раннего события. Delivery безусловно записывает
`delivered`. При повторной или пришедшей не по порядку Delivery уже открытое письмо вернётся в
менее продвинутый статус.

Нужна явная таблица допустимых переходов и обработка out-of-order/replayed events.

### 22. SMTP port почти не валидируется

Статус: **риск конфигурации**.

Где:

- `src/Console/Aws/AwsCommand.php`;
- `src/Console/Aws/SetupCommand.php`.

Любое значение приводится к `int`. Только точный `465` выбирает `ssl`, всё остальное — `tls`.
Ноль, отрицательное число или случайная строка становятся допустимым output и приводят к ошибке
только при фактическом подключении.

Нужна ранняя проверка поддерживаемых SES SMTP ports и соответствующего encryption mode.

### 23. `MAIL_FROM_ADDRESS` и `MAIL_FROM_NAME` жёстко выводятся из domain

Статус: **ограничение конфигурации**.

Где:

- `src/Console/Aws/SetupCommand.php`.

Команда всегда выдаёт:

```dotenv
MAIL_FROM_ADDRESS=info@{domain}
MAIL_FROM_NAME={domain}
```

INI не позволяет задать реальный адрес и человекочитаемое имя отправителя. Это не AWS-ошибка,
но готовый result выглядит окончательным и может затереть нужные значения при слепом копировании.

Желаемое направление: отдельные optional settings с безопасными defaults либо явная пометка,
что эти две строки — шаблон.

## Низкий приоритет и документационные долги

### 24. `AdministratorAccess` предлагается как основной путь подготовки setup user

Статус: **риск безопасности документации**.

Где:

- `docs/ru/aws/createAwsADmin.md`.

Инструкция выдаёт IAM user managed policy `AdministratorAccess` и создаёт долгоживущий access
key. Для bootstrap это удобно, но намного шире необходимых IAM/SES/SNS действий. Команда также
не умеет session token, что мешает использовать предпочтительные временные credentials.

Желаемое направление:

- документировать отдельную least-privilege policy;
- ограничить ресурсы/account/region там, где API позволяет;
- описать обязательное удаление setup key после работы;
- в перспективе использовать стандартную AWS SDK credential provider chain и роли.

Источник: [AWS IAM security best practices](https://docs.aws.amazon.com/IAM/latest/UserGuide/best-practices.html).

### 25. Вывод configuration set различается между двумя ветками setup

Статус: **UX / документация**.

При выпуске project key result содержит:

```dotenv
#AWS_SES_CONFIGURATION_SET={name}
```

При отказе от выпуска `showNotes()` печатает:

```dotenv
AWS_SES_CONFIGURATION_SET={name}
```

Причина комментария не объясняется рядом с output. Пользователь может оставить строку
закомментированной и не получать события отправки.

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
