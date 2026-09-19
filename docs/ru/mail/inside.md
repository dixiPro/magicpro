# Почта: устройство и сопровождение

Документ предназначен для разработчика, который меняет почтовую подсистему.
Внешнее использование описано в `use.md`.

## Состав подсистемы

| Часть | Файл | Ответственность |
| --- | --- | --- |
| Командный API | `src/Mail/API_Mail.php` | валидация, отправка, очередь, списки и удаление |
| Диспетчер | `src/Mail/AbstractMailApi.php` | выбор обработчика и единая обёртка ответа |
| Webhook | `src/Mail/AwsHookHandler.php` | SNS confirmation и события SES |
| Сообщение | `database/Models/MagicProMailMessage.php` | письмо, состояние и история ошибок |
| Адрес | `database/Models/MagicProEmailAddress.php` | нормализованный адрес и блокировка |
| Короткий фасад | `src/Helpers/MproHelper.php` | `MproHelper::sendMail()` |
| Планировщик | `src/Scheduling/MagicProSchedule.php` | ежеминутный запуск очереди |
| Маршруты | `routes/admin.php`, `routes/site.php` | административный API; публичный AWS webhook |

Почта использует базу как журнал и очередь. Сообщение хранит готовый HTML и
сырой MIME; шаблоны, вложения и списки получателей находятся вне подсистемы.

## Командный слой

`API_Mail` объявляет карту команд:

```text
sendNow
sendLater
sendQueue
emailQueue
messagesList
addressesList
deleteEmail
deleteQueueByEmail
```

Обработчики `protected`: снаружи они доступны только через
`API_Mail::run($command, $params)` или `handle(Request)`.

### AbstractMailApi::run

Алгоритм:

1. создаёт экземпляр конкретного API;
2. добавляет `command` в параметры;
3. проверяет команду в `$map` и существование метода;
4. вызывает обработчик;
5. ловит любой `Throwable`.

Успех:

```php
[
    'status'   => true,
    'errorMsg' => '',
    'data'     => $handlerResult,
    'request'  => $params,
]
```

Ошибка:

```php
[
    'status'   => false,
    'line'     => $exceptionFile . ' ' . $exceptionLine,
    'errorMsg' => $exceptionMessage,
    'request'  => $params,
]
```

Форма одна и та же при удаче и при ошибке: в ошибочной ветке `data` пустой
массив, проверять его наличие не нужно.

Класс — отдельный fork общего `MagicProSrc\Api\AbstractApi`, и отличий сейчас
два: у общего есть `runOrFail()` для внутренних вызовов, которым нельзя
провалиться, а в ответе об ошибке он отдаёт `line` с файлом и строкой. Почтовый
`line` наружу не отдаёт — путь и строка уходят в лог `mail`, потому что этот
ответ уезжает в браузер.

Обработчики поэтому работают до первой ошибки и бросают исключение. Исключение
не нужно ловить внутри команды, если только команда не обязана продолжить
обработку остальных элементов, как `sendQueueLocked()`.

### HTTP-вход

```text
POST /a_dmin/api/mailSystem
```

Маршрут защищён middleware `magic.auth`, но исключён из CSRF middleware.
`handle()` берёт все параметры запроса, удаляет внешний `command`, затем
передаёт его в `run()`, который снова добавляет команду в `request` ответа.

Экран админки находится на `/a_dmin/mailSystem`.

## Структура данных

Внешних ключей между таблицами нет. Логическая связь проходит по нормализованной
строке:

```text
magicPro_mail_messages.to_email
        ⇅
magicPro_email_addresses.email
```

Удаление письма не удаляет адрес. Блокировка адреса применяется к будущим
отправкам и не переписывает историю всех старых сообщений.

### magicPro_mail_messages

| Колонка | Тип | Null | Умолчание | Назначение |
| --- | --- | --- | --- | --- |
| `id` | bigint | нет | auto | внутренний ID |
| `provider_message_id` | string, unique | да | `null` | Message-ID транспорта, ключ webhook |
| `mail_id` | string, unique | да | `null` | собственный UUID, заголовок `X-MagicPro-Mail-ID` |
| `from_email` | string | нет | — | адрес отправителя |
| `from_name` | string | да | `null` | имя отправителя |
| `to_email` | string | нет | — | нормализованный получатель |
| `reply_to` | string | да | `null` | Reply-To |
| `subject` | string | нет | — | тема |
| `html` | longText | нет | — | готовое HTML-тело |
| `raw_message` | longText | нет | — | полный MIME; до первой отправки используется пустая строка |
| `scheduled_at` | timestamp | да | `null` | срок следующей отправки |
| `sent_at` | timestamp | да | `null` | время принятия транспортом |
| `status` | string | нет | модель: `queued` | текущее состояние |
| `errors` | json | да | `null` | накопленная история ошибок |
| `attempts` | integer | нет | `0` | число выполненных попыток отправки |
| `created_at`, `updated_at` | timestamp | да | Laravel | временные метки модели |

Индексы:

- unique на `provider_message_id`;
- unique на `mail_id`;
- составной индекс `to_email, subject` для поиска дублей;
- индекс `status` для очереди и админки;
- индекс `scheduled_at` для выбора готовых к отправке писем.

`MagicProMailMessage` приводит `scheduled_at` и `sent_at` к datetime, `errors` к
array, `attempts` к integer. Модель задаёт `queued` и `0`, когда значения не
переданы через Eloquent.

`appendError()` добавляет произвольный массив в конец `errors` и сразу сохраняет
модель. Текущий API пишет элементы вида:

```php
[
    'ts'      => now()->toDateTimeString(),
    'message' => $transportError,
]
```

### magicPro_email_addresses

| Колонка | Тип | Null | Умолчание | Назначение |
| --- | --- | --- | --- | --- |
| `id` | bigint | нет | auto | внутренний ID |
| `email` | string, unique | нет | — | адрес в нижнем регистре без крайних пробелов |
| `ip_address` | string | да | `null` | IP первого запроса, создавшего адрес |
| `blocked` | boolean | нет | `false` | запрет будущих отправок |
| `block_reason` | text | да | `null` | причина, обычно `Bounce` или `Complaint` |
| `blocked_at` | timestamp | да | `null` | время блокировки |
| `created_at`, `updated_at` | timestamp | да | Laravel | временные метки модели |

Мутатор `setEmailAttribute()` всегда обрезает пробелы и приводит email к нижнему
регистру. `isBlocked()` только проверяет флаг. `block()` нормализует адрес и
выполняет `updateOrCreate()`, устанавливая причину и время.

## Статусы и переходы

| Статус | Кто устанавливает | Значение |
| --- | --- | --- |
| `queued` | `sendLater()` | ожидает первой попытки |
| `sending` | `sendNow()` до транспорта, очередь при захвате письма | отправляется, транспорт ещё не ответил |
| `retrying` | `sendNow()` или очередь после ошибки | ожидает повтора |
| `sent` | успешный транспорт | транспорт принял письмо |
| `delivered` | webhook `Delivery` | SES сообщил о доставке |
| `open` | webhook `Open` | SES сообщил об открытии |
| `failed` | очередь | следующего интервала ретрая нет |
| `emailblocked` | webhook `Bounce`/`Complaint` | событие заблокировало адрес |

`QUEUE_STATUSES` содержит только `queued` и `retrying`. `messagesList()` считает
их разделом `queue`, а любой другой статус — разделом `sent`.

`sending` — не очередь: очередь такие строки не берёт. Строка, застрявшая в
`sending` (процесс убили посреди отправки), так и остаётся: ушло письмо или
нет, неизвестно, а второе письмо хуже пропавшей отметки.

Webhook не проверяет переходы в общем виде: совпавшее событие записывает новое
значение. Одно правило есть — `Delivery` не перебивает `open`: события приходят
вразнобой, и позднее «доставлено» не должно откатывать уже открытое письмо.

## Подготовка параметров письма

Обе команды отправки начинают с `buildLetterParams()`:

1. `checkEmail($params['to'] ?? '')` нормализует получателя, проверяет формат,
   создаёт адрес при первом обращении и проверяет блокировку. Адрес ищется
   `first()`, а создаётся `createOrFirst()`: два первых письма на новый адрес в
   одну секунду раньше оба вставляли строку, и второе падало на unique;
2. `subject` и `html` приводятся к строкам;
3. пустые `from` и `fromName` заменяются `config('mail.from.address')` и
   `config('mail.from.name')`;
4. непустой `replyTo` валидируется как email;
5. проверяется минимум 8 символов темы и 16 символов HTML.

Порядок внутри `checkEmail()` важен: формат проверяется **до** реестра. Иначе
строка вроде `not-an-email` заводила бы себе адрес и оставалась там навсегда,
хотя письмо всё равно будет отклонено. Пустой `to` даёт `to (email) required`,
неверный по формату — `to (email) is not an email address`.

`findDduplicates()` проверяет `to` ещё раз, уже своей валидацией: он вызывается
и отдельно.

### Проверка дублей

`findDduplicates()` повторно нормализует и валидирует `to`, затем ищет последнее
по `id` сообщение с точной парой `to_email + subject`.

```text
последнее queued/retrying/sending
    → duplicate email

последнее имеет sent_at и прошло меньше retryTimeEmail секунд
    → too frequent send to this address

иначе
    → отправка разрешена
```

`retryTimeEmail` читается через `config('magicpro_mail.retry_time')` (`src/Config/magicMail.php`), по умолчанию `60`.

Проверка и вставка строки идут под замком `letterLock()` на пару «адрес +
тема»: `Cache::lock('API_Mail::letter:<md5>', 30)`, ожидание 3 секунды. Без него
два одинаковых запроса в одну секунду оба проходили проверку — ни один ещё не
записал своё письмо. Замок держится только до вставки: дальше второе такое же
письмо остановит сама строка (`queued` или `sending`). Не дождались — `duplicate
email`. Кеш не даёт замка вовсе — отправка идёт без него.

## Немедленная отправка

### Путь sendNow

```text
buildLetterParams()
        │
        ▼
letterLock() ─ findDduplicates() ─ INSERT status=sending, mail_id=uuid ─ снять замок
        │
        ▼
AWS_SesV2Client ? sendByAwsApi() : sendBySmtp()   (с тем же mail_id)
        │
        ├── success → UPDATE status=sent, sent_at, provider_message_id, raw_message
        │
        └── error   → UPDATE status=retrying, errors=[...]
                      → throw → AbstractMailApi status=false
```

Строка вставляется **до** внешней отправки. Раньше было наоборот: письмо
уходило, INSERT падал — вызывающий получал отказ за доставленное письмо, а в
базе не оставалось строки ни для webhook, ни для защиты от повтора. Теперь база
не пишет — письмо не уходит вовсе.

Если письмо ушло, а UPDATE упал, ответ всё равно успешный (письмо у
провайдера), беда уходит в лог `mail`, строка остаётся в `sending`. Возвращаются
`id`, `mail_id`, `provider_message_id`, `status`.

При ошибке транспорта строка получает `retrying` и первую ошибку, `attempts = 1`.
`scheduled_at` не задаётся, поэтому ближайший проход очереди увидит сообщение
готовым к отправке.

Ошибки до транспорта — входные данные, блокировка, дубль — строки сообщения не
создают.

### MproHelper::sendMail

Хелпер преобразует внешние ключи:

```text
email → to
subj → subject
html → html
replyTo → replyTo
fromName → fromName
```

Адрес `from` через хелпер передать нельзя. После `API_Mail::run('sendNow')`
хелпер преобразует отрицательный status в исключение внутри собственного `try`,
ловит его, пишет результат в лог `mail` и возвращает сокращённый массив
`status/errorMsg/data`.

## Транспорты

Оба транспорта сначала создают `Symfony\Component\Mime\Email` с From, To,
Subject и HTML, при необходимости добавляют Reply-To и возвращают одинаковую
форму:

```php
[
    'status'              => true|false,
    'mail_id'             => '...',
    'provider_message_id' => '...',
    'raw_message'         => '...',
    'errorMsg'            => '',
]
```

Они ловят `Throwable` и не пробрасывают транспортное исключение. На ошибке
`provider_message_id` и `raw_message` возвращаются пустыми.

### Собственный mail_id

Если `mail_id` не передан, транспорт создаёт `Str::uuid()`. Он добавляется в MIME
как:

```text
X-MagicPro-Mail-ID: {uuid}
```

При повторной попытке очередь передаёт сохранённый `mail_id`, поэтому все
попытки одной строки используют один внутренний идентификатор. У нового
`sendLater` mail_id пуст до первой попытки.

`provider_message_id` появляется только после успеха транспорта и используется
webhook для поиска строки.

### SMTP

`sendBySmtp()` вызывает:

```php
Mail::getSymfonyTransport()->send($email)
```

Из `SentMessage` берутся provider Message-ID и окончательный сырой MIME. Если
`AWS_SES_CONFIGURATION_SET` непуст, в MIME добавляется заголовок
`X-SES-CONFIGURATION-SET`.

SMTP используется:

- `sendNow`, когда `AWS_SesV2Client` false;
- всегда внутри `sendQueueLocked()`.

Чтобы SMTP-письма дали события SES/SNS, SMTP должен вести в SES и configuration
set должен быть настроен.

### Amazon SES API v2

`sendByAwsApi()` создаёт `SesV2Client` из `config('services.ses')`:

```text
region
key
secret
```

Письмо отправляется как raw content через `sendEmail()`. Если задан
`AWS_SES_CONFIGURATION_SET`, он передаётся полем `ConfigurationSetName` запроса.

Этот транспорт используется только немедленным `sendNow` при truthy
`AWS_SesV2Client`. Очередь на него не переключается.

## Отложенная отправка

`sendLater()` выполняет те же проверки параметров и дублей, но транспорт не
вызывает. Если `scheduled_at` отсутствует или равен пустой строке, подставляется
`now()->addSeconds(60)`.

Создаётся строка:

```text
status       = queued
attempts     = 0
raw_message  = ''
mail_id      = null
scheduled_at = переданное время или now + 60 seconds
```

Отдельной валидации `scheduled_at` нет; преобразование возложено на datetime cast
модели и базу.

## Обработка очереди

### Регистрация в scheduler

`MagicProSchedule::register()` добавляет callback с именем
`magicpro:sendQueue`, который каждую минуту вызывает:

```php
API_Mail::run('sendQueue', [])
```

Laravel `withoutOverlapping()` не используется, потому что lock находится
внутри команды.

### Общий lock

`sendQueue()` получает:

```php
Cache::lock('API_Mail::sendQueue', 300)
```

Используется `get()` без ожидания. Если lock занят, команда возвращает нули во
всех счётчиках. Lock освобождается в `finally`.

### Выборка

`sendQueueLocked()` читает пачками по `QUEUE_BATCH` (50) строк по порядку `id`:

- статус `queued` или `retrying`;
- `scheduled_at IS NULL` или `scheduled_at <= now()`.

Новые письма берутся `QUEUE_SECONDS` (240) секунд — проход укладывается в срок
замка (300), остальное уходит следующей минутой. Раньше все строки грузились
одним `get()`, с html целиком, и долгий проход переживал замок: следующий
процесс начинал слать те же письма.

### Захват письма

Перед отправкой письмо забирается одним UPDATE с условием на прежний статус:

```php
MagicProMailMessage::whereKey($id)->where('status', $old)->update(['status' => 'sending']);
```

Изменилось ноль строк — письмо уже забрал кто-то другой, оно пропускается. Так
одно письмо уходит один раз, даже если два прохода всё-таки встретились. После
захвата модель синхронизируется (`syncOriginal()`): иначе `update()` на
`retrying` у письма, которое и было `retrying`, не записал бы статус, и в базе
остался бы `sending`.

### Обработка сообщения

Каждое забранное письмо отправляется через SMTP (`sendQueued()`). Существующий `mail_id` передаётся в
транспорт, пустой заменяется новым UUID.

При успехе:

```text
status              = sent
sent_at              = now
attempts             = attempts + 1
mail_id              = transport mail_id
provider_message_id  = transport Message-ID
raw_message          = transport MIME
```

При ошибке `attempts` сначала увеличивается. `nextSchedule()` работает так:

| attempts после ошибки | Действие |
| --- | --- |
| `1` | `retrying`, `scheduled_at = now + 5 minutes` |
| `2` | `retrying`, `scheduled_at = now + 10 minutes` |
| `3` | `retrying`, `scheduled_at = now + 30 minutes` |
| любое другое | исключение, которое переводит строку в `failed` |

После обновления статуса ошибка дописывается через `appendError()`. Ошибка
самого SMTP не останавливает цикл, потому что транспорт возвращает отрицательный
массив. Письмо ушло, а UPDATE упал — беда в лог `mail`, строка остаётся в
`sending` и второй раз не уйдёт. Прочие ошибки базы выходят из цикла в
`AbstractMailApi`.

Счётчик `failed` увеличивается при каждом отрицательном ответе транспорта,
поэтому `total` всегда равен `sent + failed`. Судьбу этих писем показывают ещё
два числа: `retrying` — получили новую дату, `stopped` — исчерпали попытки.

## Списки и удаление

### emailQueue

Валидирует `email`, выбирает только `queued`/`retrying`, сортирует по
`scheduled_at` и `id`. Возвращает ограниченный набор колонок без HTML, MIME и
ошибок.

### messagesList

`section === 'queue'` выбирает `QUEUE_STATUSES`; любое другое значение выбирает
остальные статусы. Поиск — `LIKE %search%` по `to_email`.

`count` по умолчанию `30`; значение меньше `1` сбрасывается к `30`. `offset`
ограничивается снизу нулём. Положительного верхнего предела у `count` в коде нет.

Для очереди сортировка идёт по `scheduled_at`, затем `id`. Для остальных — по
`sent_at DESC`, затем `id DESC`. Каждая строка включает HTML, raw MIME и errors.

### addressesList

Фильтрует адреса через `LIKE %search%`, сортирует по email и использует те же
правила `count`/`offset`. Возвращает блокировку, причину и время.

### deleteEmail

При положительном `id` ищет строго его. Иначе принимает `MessageId` или
`message_id` и ищет совпадение в `provider_message_id` либо `mail_id`. Удаляется
первая найденная строка.

### deleteQueueByEmail

Валидирует `email`, нормализует его и массово удаляет строки этого адреса только
в статусах `queued` и `retrying`.

## Webhook SES через SNS

Публичный маршрут:

```text
POST /awsHook
```

У маршрута нет `magic.auth`, CSRF middleware отключён — иначе Amazon сюда не
достучится. Значит единственное, что отделяет наше событие от подделанного, —
подпись, и она проверяется первой.

### Подпись SNS

`SnsSignature::valid()`, `src/Mail/SnsSignature.php`. Порядок:

1. `Type` должен быть одним из подписываемых: `Notification`,
   `SubscriptionConfirmation`, `UnsubscribeConfirmation`;
2. `SigningCertURL` — только `https` и только хост вида
   `sns.<регион>.amazonaws.com`. **Это несущая проверка**: без неё подделыватель
   указывает свой сервер, подписывает своим ключом, и подпись сходится;
3. сертификат берётся по этому адресу и кешируется на сутки;
4. собирается каноническая строка — `поле\nзначение\n` для полей в том порядке,
   в каком их подписывает Amazon. У `Notification` это `Message`, `MessageId`,
   `Subject` (если есть), `Timestamp`, `TopicArn`, `Type`; у подтверждений
   подписки вместо `Subject` — `SubscribeURL` и `Token`;
5. `openssl_verify` с sha1 или sha256 — по `SignatureVersion` 1 или 2.

Не сошлось — ответ `403` и строка в лог. Дальше конверт не идёт: ни статус
письма, ни адрес для ответного стука из него не берутся.

`aws/aws-sdk-php` умеет то же самое классом `Sns\MessageValidator`, но в этой
установке лежит обрезанная сборка sdk без него, а заводить зависимость ради
одного вызова openssl незачем.

`MagicProPing` — наш собственный стук, он не подписан и ничего не меняет,
поэтому отвечается раньше всех проверок.

### Внешний конверт

`AwsHookHandler::handle()` читает raw body и декодирует JSON. Поле `Type`
определяет ветку:

| Type | Поведение |
| --- | --- |
| `MagicProPing` | вернуть `status = true`, ничего не проверять и не писать в лог |
| `SubscriptionConfirmation` | выполнить GET по `SubscribeURL`, если он ведёт на `amazonaws.com` |
| `Notification` | декодировать строку `Message` и применить SES event |
| другое или пустое | подпись не сойдётся: `403` |

Проверка адреса у `SubscribeURL` — вторая половина той же защиты: подпись
говорит, что конверт от Amazon, а она — что и стучимся мы в Amazon. Запрос,
который сервер делает по адресу извне, иначе становится ходом во внутреннюю
сеть.

В лог пишутся `Type`, `MessageId` и `TopicArn`, а не тело запроса: в теле едет
всё событие письма и `SubscribeURL` вместе с токеном.

### События SES

Тип читается из `eventType`, с fallback на `notificationType`. Message-ID берётся
из `mail.messageId`. Строка ищется только по `provider_message_id`.

| Событие | Изменение |
| --- | --- |
| `Delivery` | сообщение получает `delivered` |
| `Open` | сообщение получает `open`, если его `updated_at` старше 10 секунд |
| `Bounce` | адрес блокируется с причиной `Bounce`, сообщение получает `emailblocked` |
| `Complaint` | адрес блокируется с причиной `Complaint`, сообщение получает `emailblocked` |

Неизвестное событие игнорируется. Если сообщение не найдено, записывается
`provider_message_id`, но HTTP-ответ остаётся успешным.

Фильтр Open основан на `updated_at` сообщения. Delivery обновляет это поле,
поэтому Open, пришедший в следующие 10 секунд, игнорируется.

## Инварианты при изменении кода

### Новый статус

Нужно синхронизировать:

1. константы `MagicProMailMessage::STATUS_*`;
2. `API_Mail::QUEUE_STATUSES`, если статус означает будущую отправку;
3. выборки `sendQueueLocked()`, `emailQueue()` и `deleteQueueByEmail()`;
4. разделы `messagesList()` и отображение админки;
5. переходы webhook;
6. защиту от дублей;
7. документацию.

### Новый транспорт

Транспорт должен вернуть ту же форму `status`, `mail_id`,
`provider_message_id`, `raw_message`, `errorMsg`. Нужно отдельно решить:

- как он выбирается в `sendNow`;
- должна ли через него идти очередь;
- как передаётся `mail_id`;
- как получаются события доставки;
- где хранится provider Message-ID;
- какие ошибки должны создавать `retrying`.

### Изменение ретраев

Интервалы определяет `nextSchedule($attempts)`, но начальные значения задают
сразу два пути:

- `sendLater` создаёт `attempts = 0`;
- ошибка `sendNow` создаёт `attempts = 1` без `scheduled_at`.

Изменение только массива интервалов может по-разному повлиять на эти сценарии.
Проверять нужно обе последовательности и значение счётчика на окончательном
`failed`.

### Изменение структуры ответа

Нужно проверить:

- обе ветки `AbstractMailApi::run()`;
- HTTP-клиенты админки;
- `MproHelper::sendMail()`, который сокращает ответ;
- внутренние вызовы `API_Mail::run()`;
- отсутствие или наличие `data` при ошибке.

### Изменение таблиц

Новые колонки требуют новой миграции, обновления `$fillable`, `$casts`, выборок
со списком колонок и административного интерфейса. Старые миграции не изменяются.

## Проверка после правок

Изменённые PHP-файлы проверяются через `php -l`. Тесты в `src/test/API_MailTest*`
содержат сценарии реальной отправки и работы с базой; по правилам репозитория их
нельзя запускать без отдельного разрешения.

Состояние scheduler и доступность `/awsHook` проверяются диагностикой на главной
странице админки. Настройка SES/SNS и консольная диагностика описаны в
`docs/ru/aws/`.
