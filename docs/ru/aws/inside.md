# AWS в MagicPro: внутреннее устройство

Эта страница предназначена для разработчика, который меняет AWS-интеграцию MagicPro. Здесь
зафиксированы текущие контракты команд, форматы данных, порядок AWS-вызовов, границы
безопасности и связанные части почтового модуля.

Пользовательский сценарий находится в `use.md`.

## Карта реализации

| Компонент | Назначение |
|---|---|
| `src/Console/Aws/AwsCommand.php` | Общая база команд: INI, credentials, AWS clients, имена ресурсов, вывод |
| `src/Console/Aws/SetupCommand.php` | Изменяющая команда `magicpro:aws-setup` |
| `src/Console/Aws/StatusCommand.php` | Читающая команда `magicpro:aws-status` |
| `src/Console/Aws/Subscriptions.php` | Поиск, создание, ожидание и удаление SNS subscriptions |
| `src/Mail/AwsHookHandler.php` | HTTP endpoint для ping и сообщений SNS |
| `src/Mail/SnsSignature.php` | Канонизация и проверка подписи SNS |
| `src/Mail/API_Mail.php` | Отправка через SES v2 API и передача configuration set |
| `src/Mail/MagicMailJob.php` | Очередь отправки; текущий путь — SMTP |
| `routes/site.php` | Регистрация публичного `POST /awsHook` без CSRF |
| `src/Install/Installer.php` | Реализация самопроверки webhook тем же MagicProPing |
| `src/MagicServiceProvider.php` | Регистрация console commands |

AWS-часть не владеет отдельными таблицами БД. Она использует почтовые модели и изменяет статус
письма по `provider_message_id`; bounce и complaint дополнительно блокируют адрес получателя.

## Общая модель состояния

Состояние интеграции распределено между несколькими системами:

| Место | Что хранится |
|---|---|
| `aws-setup.ini` | Желаемые region, domain, user, SMTP port и webhook |
| AWS IAM | Пользователь проекта, inline policy, access keys |
| AWS SES v2 | Domain identity, account sandbox/quota, configuration set, event destination |
| AWS SNS | Topic, topic policy, subscriptions |
| Окружение Laravel | Project key, SMTP credentials, API flag, configuration set |
| `*.result` | Одноразовая открытая копия вновь созданных секретов |
| Laravel cache | Сертификат подписи SNS на 86400 секунд |
| Почтовые таблицы | SES message ID, статусы писем, заблокированные адреса |

Ни setup, ни status не сравнивают эту модель целиком. Setup применяет отдельные именованные
ресурсы, а status показывает только SES/SNS и часть конфигурации приложения.

## CLI-контракты

### Setup

```text
magicpro:aws-setup {--file=aws-setup.ini} {--out=}
```

`--file`:

- абсолютный путь используется напрямую;
- относительный путь разрешается через `base_path()`;
- файл читается `parse_ini_file(..., true, INI_SCANNER_NORMAL)`;
- секции INI не требуются; код обращается к верхнеуровневым ключам;
- отсутствующий, нечитаемый или синтаксически неверный файл приводит к исключению;
- пробельные строковые значения обрезаются.

`--out`:

- используется только при выпуске нового project key;
- абсолютный путь используется напрямую;
- относительный путь разрешается через `base_path()`;
- пустое значение создаёт имя с доменом и timestamp до секунды.

### Status

```text
magicpro:aws-status {--file=aws-setup.ini}
```

Использует тот же INI и тот же интерактивный ввод setup credentials. Изменяющих AWS-вызовов
в штатном пути у status нет.

### Интерактивность

Обе команды требуют:

- Laravel console input в interactive mode;
- определённый `STDIN`;
- `stream_isatty(STDIN) = true`.

Setup credentials читаются через `secret()`. Команды нельзя штатно запускать в CI через pipe,
флаги или переменные окружения. AWS SDK config содержит только:

```php
[
    'version' => 'latest',
    'region' => $settings['region'],
    'credentials' => [
        'key' => $accessKey,
        'secret' => $secretKey,
    ],
]
```

Session token и стандартная provider chain AWS SDK не используются.

## Контракт INI

После чтения формируется массив:

```php
[
    'region' => string,
    'domain' => string,
    'user' => string,
    'smtp_port' => int,
    'webhook' => string,
]
```

Правила:

| Поле | Правило |
|---|---|
| `region` | обязательная непустая строка |
| `domain` | обязательная непустая строка |
| `user` | обязательная непустая строка |
| `smtp_port` | `(int)` от значения, default `587`; диапазон не проверяется |
| `webhook` | default `/awsHook`; если первый символ `/`, строится `https://{domain}{webhook}` |

Полный webhook, не начинающийся с `/`, остаётся без изменений. Схема, host, path, query и
соответствие `domain` заранее не валидируются. Перед подпиской отдельная проверка потребует
строчное начало `https://`.

## Производные имена

`AwsCommand::names()` возвращает:

```php
[
    'user' => $user,
    'policy' => $user . '-policy',
    'topic' => $user . '-events',
    'config_set' => $user,
    'event_destination' => $user . '-sns',
]
```

Одно входное имя используется сразу в разных пространствах AWS. `AwsCommand::checkUser()`
проверяет его до первого вызова AWS по пересечению правил: латинские буквы, цифры, дефис и
подчёркивание, не длиннее 60 (event destination — 64, из них `-sns` занимает 4). Любое
изменение схемы имён должно учитывать повторный запуск и существующие production-ресурсы;
автоматическое переименование создаст параллельный набор вместо миграции.

## `AwsCommand`

Базовый класс отвечает за:

- парсинг настроек;
- проверку `user` (`checkUser()`);
- скрытый ввод setup key/secret;
- создание `SesV2Client`, `SnsClient`, `IamClient`;
- общий перевод AWS exception в короткое сообщение;
- извлечение account ID из ARN;
- форматированный console output.

`accountFromArn()` делит ARN по `:` и берёт элемент с индексом 4. Account ID topic затем
используется в policy.

`.gitignore` команды не трогают: файл результата по умолчанию лежит в
`storage/app/private/magic/aws/`, который и так вне git.

## Полный поток setup

Высокоуровневый порядок в `handle()`:

```text
read settings
check smtp_port and user            — до всякого AWS
confirm settings
read setup credentials
try
  check domain and account
  create/reuse IAM user
  replace inline send policy
  ask whether to issue project key
  if yes
    create project access key (old ones untouched)
    derive SMTP credentials
    collect MAIL_FROM values
    write result file at once       — секрет AWS показывает один раз
  configure SES/SNS events and subscription
catch Throwable
  print error, failed = true
if project key was created
  write result file again (temp + rename)
else
  show notes
return failed ? FAILURE : SUCCESS
```

Rollback отсутствует. Каждый завершившийся AWS-вызов остаётся в силе после следующей ошибки.

## Проверка SES domain

`checkDomain()` вызывает:

```text
SESv2.GetEmailIdentity(EmailIdentity = domain)
SESv2.GetAccount()
```

Условие продолжения:

```text
VerifiedForSendingStatus is truthy
AND
DkimAttributes.Status === "SUCCESS"
```

Другие состояния DKIM, включая временные, считаются неготовыми. Account response используется
для вывода production/sandbox; sandbox не блокирует настройку.

## IAM user и inline policy

### Пользователь

Алгоритм:

1. `GetUser(UserName)`;
2. если AWS вернул `NoSuchEntity`, `CreateUser`;
3. при создании добавить tags `magicpro`, `domain`, `created`;
4. если user существует, использовать его без обновления tags — теги отметка
   создания, а не настройка.

Значения тегов при создании:

```text
magicpro = true
domain   = {domain}
created  = current ISO-8601 timestamp
```

Изменение domain в INI не обновит тег существующего пользователя — так задумано.

### Policy

`PutUserPolicy` полностью заменяет inline policy `{user}-policy` документом версии
`2012-10-17`:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "ses:SendEmail",
        "ses:SendRawEmail"
      ],
      "Resource": "*"
    }
  ]
}
```

Policy относится к project user, а не к setup operator. Ею, как и политикой топика, владеет
MagicPro: каждый запуск записывает её целиком, ручные statements не сохраняются.

## Выпуск project access key

`accessKey()` выполняет:

```text
ListAccessKeys(UserName)
if keys >= 2 → error, list them, nothing touched
CreateAccessKey(UserName)
```

Старые ключи не деактивируются и не удаляются: сайт шлёт письма старым, пока новый не встал
в `.env`. Удаление — `magicpro:aws-remove --key=…`.

Созданные значения временно хранятся в памяти процесса:

```php
[
    'AWS_SesV2Client' => 'true',
    'AWS_ACCESS_KEY_ID' => string,
    'AWS_SECRET_ACCESS_KEY' => string,
    'AWS_DEFAULT_REGION' => $region,
]
```

Secret key доступен AWS только в ответе `CreateAccessKey`, поэтому файл результата пишется
сразу после выпуска, до настройки events.

## Вычисление SMTP credentials

SMTP username равен `AccessKeyId`. SMTP password вычисляется из IAM secret key по региональной
SigV4-подобной цепочке:

```text
kDate    = HMAC-SHA256("AWS4" + secret, "11111111", raw)
kRegion  = HMAC-SHA256(kDate, region, raw)
kService = HMAC-SHA256(kRegion, "ses", raw)
kTerm    = HMAC-SHA256(kService, "aws4_request", raw)
kMessage = HMAC-SHA256(kTerm, "SendRawEmail", raw)
password = Base64(0x04 + kMessage)
```

Дата `11111111` и version byte `0x04` являются частью алгоритма SES, а не текущей датой.

SMTP output:

```php
[
    'MAIL_MAILER' => 'smtp',
    'MAIL_HOST' => "email-smtp.{$region}.amazonaws.com",
    'MAIL_PORT' => (string) $smtpPort,
    'MAIL_USERNAME' => $accessKeyId,
    'MAIL_PASSWORD' => $derivedPassword,
    'MAIL_ENCRYPTION' => $smtpPort === 465 ? 'ssl' : 'tls',
]
```

Порт не валидируется. Например, `0` или `25` будут приняты, причём любое значение кроме `465`
получит `tls`.

Дополнительно:

```php
[
    'MAIL_FROM_ADDRESS' => "info@{$domain}",
    'MAIL_FROM_NAME' => $domain,
]
```

Существование `info@domain` не проверяется. При domain identity SES обычно разрешает отправку с
адресов этого домена, но прикладная политика адреса остаётся ответственностью проекта.

## SNS topic

`topic()` сначала проходит `ListTopics` со всеми страницами и ищет ARN с окончанием
`:{topicName}`. При отсутствии вызывает `CreateTopic(Name)`.

После получения ARN `SetTopicAttributes(Policy)` полностью заменяет topic policy:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "OwnerAccess",
      "Effect": "Allow",
      "Principal": {"AWS": "*"},
      "Action": "SNS:*",
      "Resource": "{topicArn}",
      "Condition": {
        "StringEquals": {"AWS:SourceOwner": "{accountId}"}
      }
    },
    {
      "Sid": "AllowSES",
      "Effect": "Allow",
      "Principal": {"Service": "ses.amazonaws.com"},
      "Action": "SNS:Publish",
      "Resource": "{topicArn}",
      "Condition": {
        "StringEquals": {"AWS:SourceAccount": "{accountId}"}
      }
    }
  ]
}
```

`accountId` извлекается из ARN topic. Существующая вручную настроенная policy этого topic
перезаписывается.

## SES configuration set и event destination

`configurationSet()`:

1. вызывает `GetConfigurationSet(ConfigurationSetName)`;
2. при `NotFoundException` вызывает `CreateConfigurationSet`;
3. получает `GetConfigurationSetEventDestinations`;
4. ищет destination по точному имени;
5. создаёт или обновляет destination.

Желаемый destination:

```php
[
    'Enabled' => true,
    'MatchingEventTypes' => [
        'DELIVERY',
        'BOUNCE',
        'COMPLAINT',
        'OPEN',
    ],
    'SnsDestination' => [
        'TopicArn' => $topicArn,
    ],
]
```

Только четыре типа — те, на которые обработчик что-то делает. Раньше было восемь: `SEND`,
`CLICK`, `REJECT`, `RENDERING_FAILURE` приходили, принимались и выбрасывались. Update приводит
destination к этому точному набору. Другие destinations configuration set не удаляются.

В result output строка configuration set намеренно формируется как комментарий:

```dotenv
#AWS_SES_CONFIGURATION_SET={configSetName}
```

При отказе выпускать ключ `showNotes()` печатает ту же строку без `#`. Это расхождение UX,
которое должен учитывать вызывающий пользователь.

## Проверка webhook перед подпиской

`reachable()` делает:

```text
POST webhook
Content-Type: application/json
body: {"Type":"MagicProPing"}
timeout: 10 seconds
allow_redirects: false
```

Успех требует одновременно:

- successful HTTP status;
- JSON response;
- truthy `status` в JSON.

Исключение HTTP-клиента превращается в warning и `false`. URL берётся из доверенного локального
INI, но технически это исходящий HTTP-запрос процесса CLI.

Подписка вообще не создаётся, если endpoint не начинается с точной строки `https://` или ping
не прошёл. Настройка остальных AWS-ресурсов при этом считается завершённой.

## `Subscriptions`

### Чтение

`all()` пагинирует `ListSubscriptionsByTopic` через `NextToken` и нормализует результат:

```php
[
    [
        'protocol' => string,
        'endpoint' => string,
        'arn' => string,
        'status' => 'PENDING' | 'CONFIRMED',
    ],
]
```

Только точный ARN `PendingConfirmation` считается pending. Любое другое значение считается
confirmed.

### Создание и ожидание

`ensure(endpoint)`:

1. возвращает существующую подтверждённую HTTPS-подписку с точным endpoint;
2. если есть точная pending-подписка, не вызывает `Subscribe` повторно;
3. иначе вызывает `Subscribe(Protocol=https, Endpoint=endpoint)`;
4. до 30 раз ждёт одну секунду и перечитывает subscriptions;
5. возвращает подтверждённую подписку либо `null`.

Подтверждение выполняет HTTP webhook независимо от CLI-процесса.

### Удаление других подписок

После успешного подтверждения `dropOthers(endpoint)` удаляет только подписки, у которых:

- protocol точно `https`;
- endpoint отличается от текущего;
- status `CONFIRMED`.

Pending и подписки других протоколов остаются. Удаление выполняется `Unsubscribe` без
дополнительного подтверждения пользователя.

## Файл результата

Строки формируются в порядке:

1. API flag и AWS project credentials;
2. SMTP credentials;
3. from address/name;
4. заметки комментариями, среди них `AWS_SES_CONFIGURATION_SET` и `AWS_SNS_TOPIC_ARN`.

Формат значения — простое `KEY=value` без shell quoting или escaping.

Путь: `--out` или `storage/app/private/magic/aws/aws-{domain}-{date}.result`. Каталог
создаётся с `0700`.

Запись (`writeResult()`):

- первая — `createSecretFile()`: `umask(0077)` и `fopen(..., 'x')`, то есть файл рождается
  `0600` и никогда не ложится на существующий; существует — отказ;
- вторая, в конце прогона, — `replaceSecretFile()`: `tempnam()` рядом и `rename()`;
- каждая запись проверяется; не записалось — `FAILURE` и подсказка удалить выпущенный ключ;
- после отказа первой записи вторая не делается вовсе, чтобы не лечь на тот же чужой файл.

## Поток status

После settings и credentials каждая секция исполняется в своём `try/catch Throwable`. Ошибка
печатается, затем выполняется следующая секция. Упала хоть одна — в конце `FAILURE`.

### SES identities

Вызовы:

```text
GetEmailIdentity(domain)
GetAccount()
ListEmailIdentities()   — все страницы, по NextToken
```

### SNS topic

Поиск topic пагинируется полностью. После нахождения используется `Subscriptions::show()`.

### SES events

Вызовы:

```text
GetConfigurationSet(configSet)
GetConfigurationSetEventDestinations(configSet)
```

Выводятся все destinations: имя, enabled, matching types и SNS ARN. Содержимое topic policy не
проверяется.

### MagicPro project

Вывод читает то, что сайт реально видит, в том числе после `config:cache`:

```text
config(magicpro_mail.ses_api)
config(magicpro_mail.configuration_set)
config(mail.mailers.smtp.host)
config(services.ses.region)
```

Секретов, реальной transport connectivity и доставки status не проверяет: он только читает.

## HTTP-маршрут AWS

`routes/site.php` регистрирует:

```text
POST /awsHook
name: magic.awsHook
controller: MagicController
handler: AwsHookHandler
CSRF: excluded
authentication: none
```

Публичность и отсутствие CSRF необходимы SNS. Граница доверия должна обеспечиваться подписью
SNS и проверкой ожидаемого topic; вторая часть сейчас отсутствует.

`MagicProPing` обрабатывается до подписи и возвращает `status=true`. Он не принимает команд и не
меняет прикладное состояние.

## Форматы входящих конвертов

### Ping

```json
{
  "Type": "MagicProPing"
}
```

### Subscription confirmation

Минимально используемые поля:

```json
{
  "Type": "SubscriptionConfirmation",
  "MessageId": "...",
  "TopicArn": "arn:aws:sns:region:account:topic",
  "Message": "...",
  "Timestamp": "...",
  "Token": "...",
  "SubscribeURL": "https://sns.region.amazonaws.com/...",
  "SignatureVersion": "1",
  "Signature": "...",
  "SigningCertURL": "https://sns.region.amazonaws.com/...pem"
}
```

### Notification

```json
{
  "Type": "Notification",
  "MessageId": "...",
  "TopicArn": "arn:aws:sns:region:account:topic",
  "Subject": "optional",
  "Message": "{\"eventType\":\"Delivery\",\"mail\":{...},\"delivery\":{...}}",
  "Timestamp": "...",
  "SignatureVersion": "1",
  "Signature": "...",
  "SigningCertURL": "https://sns.region.amazonaws.com/...pem"
}
```

HTTP body обязан декодироваться в JSON object/associative array. Некорректный JSON не проходит
подпись и получает 403.

## Проверка SNS-подписи

`SnsSignature::valid()` поддерживает типы:

- `Notification`;
- `SubscriptionConfirmation`;
- `UnsubscribeConfirmation`.

### Обязательные общие поля

```text
Type
MessageId
TopicArn
Message
Timestamp
SignatureVersion
Signature
SigningCertURL
```

Для confirmation дополнительно обязательны `Token` и `SubscribeURL`.

### Каноническая строка

Для Notification:

```text
Message
{Message}
MessageId
{MessageId}
[Subject
{Subject}
]
Timestamp
{Timestamp}
TopicArn
{TopicArn}
Type
{Type}
```

Для confirmation:

```text
Message
{Message}
MessageId
{MessageId}
SubscribeURL
{SubscribeURL}
Timestamp
{Timestamp}
Token
{Token}
TopicArn
{TopicArn}
Type
{Type}
```

Каждая метка и значение заканчиваются LF. Поле `Subject` включается только при наличии.

### Сертификат

`SigningCertURL` принимается только если:

- схема точно `https`;
- host соответствует SNS endpoint вида `sns.{region}.amazonaws.com` либо
  `sns.{region}.amazonaws.com.cn`;
- path URL не ограничивается дополнительным pattern.

Сертификат скачивается Laravel HTTP client с timeout 5 секунд и кэшируется на 86400 секунд по
ключу:

```text
magicpro:sns-cert:{sha256(SigningCertURL)}
```

Используются `openssl_pkey_get_public()` и `openssl_verify()`:

| SignatureVersion | Digest |
|---|---|
| `1` | SHA-1 |
| `2` | SHA-256 |

Другая версия отклоняется. Подпись base64 декодируется в strict mode.

Текущая реализация не проверяет:

- что `TopicArn` равен настроенному topic;
- account и region из ARN;
- свежесть `Timestamp`;
- повторную доставку по `MessageId`;
- цепочку доверия сертификата отдельно от HTTPS-загрузки и публичного ключа.

Наиболее важен allowlist `TopicArn`: AWS прямо рекомендует отклонять сообщения от неожиданных
topics.

## Обработка subscription confirmation

После успешной подписи handler берёт `SubscribeURL`. URL принимается `amazonUrl()`, если:

- схема `https`;
- host равен `amazonaws.com` или оканчивается на `.amazonaws.com`.

Затем выполняется `HTTP GET` с timeout 10 секунд. Redirect policy явно не задана. Ответ не
проверяется через `successful()` и его содержимое не анализируется; после любого не бросившего
исключение ответа журнал сообщает об успешном подтверждении.

Для signing certificate разрешён `.amazonaws.com.cn`, а `amazonUrl()` его не принимает. Это
внутреннее расхождение.

## Обработка Notification

Поле `Message` повторно декодируется как JSON. Handler использует:

```text
eventType
mail.messageId
mail.destination[0]
```

Message ID ищется в `MailMessage.provider_message_id`. Отсутствующая запись приводит к warning
и успешному HTTP-ответу, чтобы SNS не повторял бесполезную доставку.

Переходы:

| eventType | Переход |
|---|---|
| `Delivery` | `status = delivered` |
| `Open` | `status = open`, только если `updated_at < now - 10 seconds` |
| `Bounce` | блокировка сохранённого `to_email`, затем `status = emailblocked` |
| `Complaint` | блокировка сохранённого `to_email`, затем `status = emailblocked` |
| прочее | без изменения |

Destination из входящего event не используется для блокировки: используется `to_email` из
найденной записи. Это уменьшает влияние подмены destination, но не заменяет проверку topic.

Handler всегда возвращает `status=true` после корректно подписанного поддерживаемого конверта,
даже если event JSON некорректен, письмо не найдено или event type игнорируется.

## Логирование webhook

До декодирования SES Message логируется только метаинформация SNS:

```text
Type
MessageId
TopicArn
```

При неправильной подписи записывается warning с теми же полями. Secret, signature и полный SES
payload штатно не логируются.

Подтверждение подписки логирует URL и результат. При изменениях сохраняйте правило: не писать в
журнал access keys, secret keys, SMTP password и полные персональные payloads без явной причины.

## Связь с отправкой почты

### Синхронная отправка

`API_Mail` выбирает путь по `config('magicpro_mail.ses_api')`. Включено — создаётся
`SesV2Client` из `config('services.ses')`; иначе используется Laravel Mailer/SMTP.

API request включает:

```php
[
    'FromEmailAddress' => string,
    'Destination' => ['ToAddresses' => array],
    'Content' => ['Raw' => ['Data' => raw MIME message]],
    'ConfigurationSetName' => config('magicpro_mail.configuration_set'), // only when not empty
]
```

### SMTP

При непустом `magicpro_mail.configuration_set` в Symfony Email добавляется:

```text
X-SES-CONFIGURATION-SET: {value}
```

### Очередь

Текущая queue job отправляет через SMTP, даже если API flag включён. Поэтому setup формирует оба
набора credentials.

### Config cache

`AWS_SesV2Client`, `AWS_SES_CONFIGURATION_SET`, `AWS_SNS_TOPIC_ARN` и `retryTimeEmail`
объявлены в `src/Config/magicMail.php` и сливаются под ключом `magicpro_mail`
(`MagicServiceProvider::register()`). Runtime-код читает только `config()`: прямой `env()`
вне config-файла после `config:cache` пуст.

## Инварианты безопасности

При изменении AWS-интеграции сохраняйте или усиливайте следующие свойства:

- setup secret никогда не печатается и не сохраняется;
- project secret показывается только один раз и пишется только в явно предназначенный output;
- webhook не доверяет полям SNS до криптографической проверки;
- certificate URL не может указывать на произвольный host;
- confirmation URL не может указывать на произвольный host;
- принимается только ожидаемый topic/account/region;
- ротация только добавляет ключ, старый удаляется отдельно после переключения;
- partial failure возвращает ненулевой exit code;
- result создаётся сразу `0600`, не перезаписывает чужой файл, запись проверяется;
- логи не содержат credentials и лишние персональные данные;
- setup operator имеет минимальные права и удаляется после bootstrap.


## Что проверять после изменений

Автоматические тесты AWS-команд и signature handler в пакете сейчас не найдены. При добавлении
тестов AWS SDK и HTTP должны быть подменены; реальные AWS, сеть, БД и production secrets тестам
не нужны.

Минимальный набор unit/feature сценариев:

### INI и имена

- обязательные поля;
- relative и absolute file;
- relative webhook и full URL;
- default port и webhook;
- некорректное имя ресурса до первого AWS mutation;
- `--out` relative/absolute и существующий файл.

### Setup

- domain verified/DKIM success;
- domain/DKIM not ready;
- sandbox warning;
- новый и существующий IAM user;
- tags существующего user;
- точный policy document;
- zero, one и two existing access keys;
- failure после `CreateAccessKey` до result write;
- port 465 и 587;
- create/update configuration set destination;
- unreachable/non-HTTPS webhook;
- pending, confirmed и competing subscriptions;
- ненулевой exit code при каждом исключении.

### Status

- pagination identities и topics;
- отсутствующий configuration set/topic;
- ошибка каждой отдельной секции;
- exit code отражает partial failure;
- IAM drift, если status будет расширен.

### Signature

- Notification с Subject и без него;
- SubscriptionConfirmation и UnsubscribeConfirmation;
- SignatureVersion 1 и 2;
- unknown type/version;
- malformed base64;
- HTTP failure и повреждённый certificate;
- forbidden cert hosts, ports и URL variants;
- expected и unexpected TopicArn;
- stale timestamp и replay после добавления защиты;
- cache success и cache failure.

### Handler

- unsigned ping;
- unsigned/invalid SNS -> 403;
- confirmation success и non-2xx response;
- invalid Message JSON;
- unknown message ID;
- Delivery/Open/Bounce/Complaint;
- ignored event types;
- события вне порядка и повторная доставка;
- signed notification от другого topic.

### Mail integration

- API/SMTP selection через config;
- configuration set в API request;
- SMTP header;
- поведение при config cache;
- queue transport;
- сохранение provider message ID.

## Порядок безопасного изменения

1. Определите, меняется ли внешний CLI/INI/env/webhook контракт.
2. Сначала добавьте тест, воспроизводящий текущий дефект или новый контракт.
3. Для AWS mutations моделируйте partial failure на каждом шаге.
4. Не запускайте setup против реального аккаунта в обычном тесте.
5. Если меняются имена ресурсов или policy, подготовьте совместимый migration path.
6. Если меняется payload handler, проверьте настоящие примеры SES для всех event types.
7. После кода синхронно обновите `use.md` и эту страницу.
8. Не добавляйте секреты, result-файлы или реальные SNS payloads с персональными данными в git.

## Официальные контракты

- [IAM access keys](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html)
- [SES SMTP credentials](https://docs.aws.amazon.com/ses/latest/dg/smtp-credentials.html)
- [SES regions](https://docs.aws.amazon.com/ses/latest/dg/regions.html)
- [SNS signature verification](https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message.html)
- [SNS signature version](https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message-configure-message-signature.html)
- [SNS subscription deletion](https://docs.aws.amazon.com/sns/latest/dg/sns-delete-subscription-topic.html)
- [SNS CreateTopic API](https://docs.aws.amazon.com/sns/latest/api/API_CreateTopic.html)
- [IAM best practices](https://docs.aws.amazon.com/IAM/latest/UserGuide/best-practices.html)
- [Laravel configuration](https://laravel.com/framework/docs/13.x/configuration)
