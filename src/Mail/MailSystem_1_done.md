# Почтовая система

## структура данных

### Таблица писем magicPro_mail_messages

id // Уникальный ID записи, unsigned bigint, auto increment
provider_message_id // Message-ID почтового провайдера, string, unique, nullable
mail_id // собственный идентификатор емайла, string, unique
from_email // Email отправителя, string
from_name // Имя отправителя (fromName), string, nullable
to_email // Email получателя, string
subject // Тема письма, string
html // Тело письма longText
raw_message // Письмо целиком вместе с заголовками, longText
scheduled_at // Планируемые дата и время отправки, timestamp, nullable
sent_at // Фактические дата и время отправки, timestamp, nullable
status // Текущий статус письма, string
errors // Ошибки доставки и обработки, JSON, nullable
created_at // Дата и время создания записи, timestamp
updated_at // Дата и время последнего изменения, timestamp
attempts // сколько попыток отправки было, целое

#### Ключи и индексы magicPro_mail_messages

id // primary key
provider_message_id // unique (матч вебхука, deleteEmail)
mail_id // unique (собственный идентификатор, матч вебхука через X-SES-MESSAGE-TAGS)
(to_email, subject) // составной index (правило повтора + очередь по адресу)
status // index (выборка кроном + фильтр админки)
scheduled_at // index (крон: пора отправлять)

### Таблица magicPro_email_addresses

id // Уникальный ID записи, unsigned bigint, auto increment
email // Email-адрес, string, unique
ip_address // IP-адрес источника данных, string, nullable
blocked // Признак блокировки, boolean, default false
block_reason // Причина блокировки, text, nullable
blocked_at // Дата и время блокировки, timestamp, nullable
created_at // Дата и время создания записи, timestamp
updated_at // Дата и время последнего изменения, timestamp

#### Ключи и индексы magicPro_email_addresses

id // primary key
email // unique (проверка блокировки перед каждой отправкой — самый частый запрос)
// blocked — index не делаем в первой версии, задачи «список заблокированных» пока нет

## транспорт в src/Mail/MagicProMailer.php

### особенности send

Отправляет через smtp

При первом вызове открывает SMTP-соединение. Следующие вызовы в рамках текущего PHP-процесса используют уже открытое соединение.

Если send() вернул status => false, это означьает ошибку SMTP-отправки готового письма.

В таком случае дальнейшую отправку в этом цикле разумно прекратить, потому что причина может быть в соединении, авторизации, лимите или ошибке SMTP-сервера.

### особенности sendByAwsApi

отправляет через POST каждый раз устанавливает соединение, использовать для отправки одного письма.

## API src\Mail\Api\API_Mail.php

## Статусы

queued / письмо создано, ждёт отправки (sendLater) / В очереди
sent / транспорт принял письмо (SES вернул MessageId) / Отправлено
delivered / транспорт доставил письмо, пришел вебхук / Доставлено
open / юзер открыл письмо / Октрыто
error / ошибка доставки / Ошибка доставки
failed / исчерпаны попытки отправки / Крах доставки
emailblocked адрес заблокирован / Заблокировано

## Методы

каждый метод работает до первой ошибки, потом выбрасывает исключение.
родитель объекта ловит исключение и формирует отрицательный ответ

### nextSchedule вычисляет время следующей отпрвки

входной параметр номер попытки

1 -> +5 минут, 2 -> +10 минут, 3 -> +30 минут.
Для номера попытки вне 1..3 попыток больше нет — выбрасывает исключение.

### findDduplicates - идет дубли

Параметры:

- to (email) (обязательно)
- Subject

Ищет в базе to+Subject
Если статус не sent выбрасывает исключение 'duplicate email'
Если время меньше чем env(retryTimeEmail) ?? 60 — выбрасывает исключение.

### sendLater положить письмо в базу

Параметры:

- from (email) / если нет берется из системы
- fromName (string) / если нет берется из системы
- to (email) (обязательно)
- replyTo (email) / если нет не создается?
- subject
- Тело письма (html)
- Планируемая дата отправки

Проверяет валидность и что емайл не заблокирован

Кладет емайл в базу

### sendNow Отправляет мгновенно.

Параметры:

- from (email) / если нет берется из системы
- fromName (string) / если нет берется из системы
- to (email) (обязательно)
- replyTo (email) / если нет не создается?
- Subject (не меньше 8-ми знаков)
- Тело письма (html) (не меньше 16 символов)

Возвращает статус и ошибку

### sendQueue отправляет письма из очереди (крон)

Берёт письма со статусом queued или error, у которых scheduled_at пуст или уже наступил,
и отправляет каждое через MagicProMailer::send(). Ошибка одного письма не
прерывает обработку остальных.

При успехе: статус sent, sent_at = now, attempts++.
При ошибке: attempts++, статус error, следующая попытка через nextSchedule(attempts);
когда попытки исчерпаны — статус failed. Текст ошибки дописывается в errors.

Возвращает total / sent / failed.

### emaiQueue Возвращает список писем для email находящихся в очереди

Очередь — письма в статусах queued и error.

Параметры:

- email (email)

### deleteEmail

Удаляет письмо

- id или
- Message-ID

### deleteQueueByEmail

Удаляет письма в статусе queued для определенного емайла

### nextSchedule вычисляет время следующей отправки

входной параметр номер попытки

### checkEmail проверяет емайл

Проверяет валидность емайла
Смотрит в базу, что бы емайл не был заблокированным

### buildLetterParams проверяет входные параметры

Собирает и валидирует параметры письма для makeEmail(): to, subject, html, from, fromName, replyTo. Общие правила для sendNow и sendLater subject не короче 8 символов, html не короче 16.
