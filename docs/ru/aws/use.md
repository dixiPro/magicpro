# AWS в MagicPro: настройка и эксплуатация

Эта страница предназначена для человека, который настраивает отправку почты через Amazon SES
и приём событий SES через Amazon SNS. Она описывает фактическое поведение текущего кода
MagicPro.

Команды создают и изменяют реальные ресурсы AWS. Перед запуском прочитайте разделы
«Два разных ключа» и «Безопасная ротация ключа»: текущая реализация ротации имеет опасное
ограничение.

## Что настраивает MagicPro

Команда `magicpro:aws-setup` приводит к следующей схеме:

```text
MagicPro --SMTP or SES API--> Amazon SES
                                  |
                                  v
                         configuration set
                                  |
                                  v
                              SNS topic
                                  |
                                  v
                     POST https://site/awsHook
                                  |
                                  v
                         mail_messages status
```

Она:

- проверяет SES identity домена и DKIM;
- находит или создаёт IAM-пользователя проекта;
- записывает ему inline policy для отправки почты;
- по запросу выпускает IAM access key и вычисляет региональный SES SMTP password;
- создаёт SNS topic и его policy;
- создаёт SES configuration set и event destination;
- проверяет webhook и оформляет HTTPS-подписку SNS;
- при выпуске ключа сохраняет готовый блок переменных окружения в файл результата.

Команда `magicpro:aws-status` только читает часть состояния AWS и конфигурации проекта. Она
не исправляет ресурсы.

## До запуска

### 1. Выберите один AWS-регион

SES identity, configuration set, event destination, SNS topic и отправляющий клиент должны
работать в одном регионе. SMTP password также вычисляется для конкретного региона. Сам IAM
access key глобальный, но полученный из него SES SMTP password региональный.

Пример региона:

```ini
region = eu-central-1
```

### 2. Подготовьте домен в Amazon SES

В выбранном регионе:

- создайте domain identity;
- добавьте выданные SES DNS-записи;
- дождитесь `VerifiedForSendingStatus = true`;
- дождитесь `DkimAttributes.Status = SUCCESS`.

Если одно из двух условий не выполнено, `magicpro:aws-setup` остановится до изменения IAM,
SNS и configuration set.

Проверьте также режим аккаунта SES. В sandbox отправка ограничена подтверждёнными адресами и
действуют пониженные квоты. Команда лишь выводит предупреждение и не запрашивает production
access.

### 3. Подготовьте публичный HTTPS webhook

Для стандартного маршрута нужен доступный из интернета адрес:

```text
https://example.com/awsHook
```

Маршрут принимает только `POST`. TLS-сертификат должен быть действительным. Redirect при
самопроверке не разрешён.

Перед созданием подписки команда посылает JSON:

```json
{
  "Type": "MagicProPing"
}
```

Ожидаемый ответ:

```json
{
  "status": true
}
```

Этот служебный ping намеренно не подписан AWS. Все сообщения SNS остальных типов проходят
проверку криптографической подписи.

### 4. Сделайте резервную копию текущих почтовых настроек

Сохраните действующие `MAIL_*`, `AWS_*` и `AWS_SES_CONFIGURATION_SET`. Старый ключ при
перевыпуске продолжает работать, но копия настроек нужна, чтобы было куда вернуться.

## Два разных ключа

Команда использует две пары credentials с разными задачами.

### Ключ настройки

Его команда запрашивает сразу после чтения INI-файла:

```text
AWS setup access key ID
AWS setup secret access key
```

Этот ключ нужен только самой команде для управления IAM, SES и SNS. Он не записывается в
проект. Ввод скрыт и возможен только в интерактивном терминале с TTY.

Не используйте root credentials AWS. Предпочтителен отдельный оператор или роль с минимально
необходимыми правами. Текущая команда принимает только access key и secret key: session token
она передать AWS SDK не умеет, поэтому временные STS credentials из трёх частей не
поддерживаются.

Требуемые текущим кодом действия:

```text
iam:GetUser
iam:CreateUser
iam:TagUser
iam:PutUserPolicy
iam:ListAccessKeys
iam:UpdateAccessKey
iam:CreateAccessKey

ses:GetEmailIdentity
ses:GetAccount
ses:ListEmailIdentities
ses:GetConfigurationSet
ses:CreateConfigurationSet
ses:GetConfigurationSetEventDestinations
ses:CreateConfigurationSetEventDestination
ses:UpdateConfigurationSetEventDestination

sns:ListTopics
sns:CreateTopic
sns:SetTopicAttributes
sns:ListSubscriptionsByTopic
sns:Subscribe
sns:Unsubscribe
```

Уточняйте Resource-ограничения policy под свой аккаунт, регион и выбранные имена. После
завершения настройки удалите или деактивируйте долгоживущий ключ оператора, если он больше не
нужен.

Ключ настройки выпускается по `createAwsADmin.md`, с политикой ровно из тех
действий, которые команды вызывают. `AdministratorAccess` там больше не
предлагается, а сам ключ рекомендуется удалить, когда настройка закончена.

### Ключ проекта

Его команда может выпустить для IAM-пользователя из параметра `user`. Он попадает в блок:

```dotenv
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
```

Из его secret key команда вычисляет отдельный `MAIL_PASSWORD` для SES SMTP. Этот пароль нельзя
заменять исходным IAM secret key.

## Файл `aws-setup.ini`

Минимальный пример:

```ini
region = eu-central-1
domain = example.com
user = example-mailer
smtp_port = 587
webhook = /awsHook
```

| Ключ | Обязателен | Значение |
|---|---:|---|
| `region` | да | AWS-регион SES и SNS |
| `domain` | да | Уже подтверждённый SES-домен; также используется как MAIL_FROM_NAME |
| `user` | да | IAM user и основа имён остальных ресурсов |
| `smtp_port` | нет | По умолчанию `587`; только `465` включает `ssl`, любое другое значение — `tls` |
| `webhook` | нет | По умолчанию `/awsHook`; путь дополняется `https://{domain}`, полный URL используется как есть |

Имена ресурсов выводятся из `user` без дополнительной нормализации:

| Ресурс | Имя |
|---|---|
| IAM user | `{user}` |
| IAM inline policy | `{user}-policy` |
| SNS topic | `{user}-events` |
| SES configuration set | `{user}` |
| SES event destination | `{user}-sns` |

`user` — латинские буквы, цифры, дефис и подчёркивание, не длиннее 60 символов:
это то, что принимают сразу IAM, SNS и SES. Команда проверяет имя до того, как
что-либо создано в AWS, и с другим именем не начнёт.

Теги IAM-пользователя (`magicpro`, `domain`, `created`) — отметка создания:
пишутся один раз, у существующего пользователя не меняются.

Inline policy `{user}-policy` и политика топика принадлежат MagicPro: каждый
запуск setup записывает их целиком. Ручные правки в них не сохраняются.

`--file` принимает абсолютный путь либо путь относительно корня Laravel-приложения:

```bash
php artisan magicpro:aws-setup --file=aws-setup.ini
```

INI-файл не должен содержать секреты.

## Первый запуск

Запускайте команду из интерактивного терминала:

```bash
php artisan magicpro:aws-setup --file=aws-setup.ini
```

Последовательность:

1. Команда читает INI и просит подтвердить регион, домен, пользователя, SMTP и webhook.
2. Запрашивает ключ настройки скрытым вводом.
3. Проверяет domain identity, DKIM и sandbox.
4. Создаёт либо повторно использует IAM user.
5. Полностью заменяет его inline policy `{user}-policy` разрешением отправки SES.
6. Спрашивает, выпускать ли новый ключ проекта.
7. Настраивает SNS topic, SES configuration set, event destination и HTTPS-подписку.
8. При выпуске ключа пишет файл результата; без выпуска показывает заметки в терминале.

До подтверждения выпуска нового ключа внимательно прочитайте следующий раздел.

## Безопасная ротация ключа

У IAM-пользователя может быть не больше двух access keys, включая неактивные.
Команда только добавляет: новый ключ выпускается рядом со старым, старый не
деактивируется и не удаляется и работает, пока новый не встал в проект.

- Свободного места нет (уже два ключа) — команда говорит об этом, показывает
  оба ключа и ничего не трогает. Лишний удаляется `magicpro:aws-remove --key=…`.
- Старый ключ удаляйте после того, как новый стоит в `.env` и письма уходят.

Если новый ключ не нужен, ответьте `no`. IAM credentials не изменятся, но SNS и SES events
будут настроены.

## Файл результата

По умолчанию файл пишется в `storage/app/private/magic/aws/`:

```text
storage/app/private/magic/aws/aws-example.com-2026-09-09_123456.result
```

Каталог не отдаётся вебом и не попадает в git; `.gitignore` команда не трогает.
Можно задать путь явно:

```bash
php artisan magicpro:aws-setup --out=/secure/path/aws.result
```

Относительный `--out` разрешается от корня Laravel-приложения. Файл создаётся
сразу с правами `0600`. Существующий файл по этому пути **не перезаписывается** —
команда откажет и попросит другой путь. Не удалось записать — команда завершится
ошибкой и подскажет удалить только что выпущенный ключ: без секрета он бесполезен.

Файл содержит открытые секреты:

```dotenv
AWS_SesV2Client=true
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=eu-central-1

MAIL_MAILER=smtp
MAIL_HOST=email-smtp.eu-central-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@example.com
MAIL_FROM_NAME=example.com

# AWS_SES_CONFIGURATION_SET=example-mailer
# AWS_SNS_TOPIC_ARN=arn:aws:sns:...
```

Строки `AWS_SES_CONFIGURATION_SET` и `AWS_SNS_TOPIC_ARN` записаны комментарием.
Уберите `#`, когда webhook отвечает: первая включает события, вторая велит
webhook принимать события только этого топика.

После переноса переменных удалите файл результата.

### Куда переносить переменные

В текущем хост-приложении Laravel загружает корневой `.env`. Механизм автоматической загрузки
Секреты копируются в `.env` проекта — тот самый, который читает Laravel.
Никакого отдельного `.env_mpro` не существует: это имя раньше стояло в
сообщениях команды и не соответствовало ничему на диске.

### `config:cache`

`AWS_SesV2Client`, `AWS_SES_CONFIGURATION_SET`, `AWS_SNS_TOPIC_ARN` и
`retryTimeEmail` читаются через конфиг пакета (`src/Config/magicMail.php`, ключ
`magicpro_mail`), поэтому `php artisan config:cache` их не теряет. Поменяли
`.env` на сайте с кешем конфигурации — пересоберите кеш.

## SMTP и SES API

MagicPro имеет два пути отправки:

| Путь | Когда используется | Credentials |
|---|---|---|
| SES API | `AWS_SesV2Client` включён в `.env` | IAM access key и secret key |
| SMTP | иначе; также используется очередью отправки | SMTP username и региональный SMTP password |

Даже при включённом API оставляйте рабочие SMTP-параметры, если приложение использует очередь:
текущая очередь отправляет через SMTP.

Чтобы SES добавлял события письма в созданный event destination, отправка должна указывать
configuration set:

- API-путь передаёт `ConfigurationSetName`;
- SMTP-путь добавляет заголовок `X-SES-CONFIGURATION-SET`.

Оба пути берут имя из `AWS_SES_CONFIGURATION_SET`.

## SNS-подписка и webhook

Подписка создаётся только если URL начинается с точного строчного `https://` и служебный ping
успешен. После `Subscribe` команда ждёт подтверждение не более 30 секунд.

Обычный процесс выглядит так:

1. SNS отправляет `SubscriptionConfirmation`.
2. MagicPro проверяет подпись конверта.
3. MagicPro делает `GET` по `SubscribeURL`, если домен URL принадлежит AWS.
4. SNS переводит подписку из `PendingConfirmation` в подтверждённую.
5. Команда видит ARN подписки и удаляет другие подтверждённые HTTPS-подписки этого topic.

Неподтверждённая подписка автоматически удаляется SNS через 48 часов.
Повторный запуск настройки создаст новую попытку.

Задан `AWS_SNS_TOPIC_ARN` — webhook принимает события только этого топика,
остальные отбрасывает. Пустой — принимает любой топик с верной подписью AWS.

## Какие события меняют состояние письма

SES event destination подписан на четыре типа — ровно те, что обработчик использует:

| Событие SES | Действие MagicPro |
|---|---|
| `Delivery` | статус письма становится `delivered`, если оно ещё не `open` |
| `Open` | статус становится `open`, если с предыдущего изменения прошло больше 10 секунд |
| `Bounce` | адрес получателя блокируется, статус становится `emailblocked` |
| `Complaint` | адрес получателя блокируется, статус становится `emailblocked` |

Остальные события SES (`Send`, `Click`, `Reject`, `Rendering Failure`) не
подписаны: обработчик с ними ничего не делал. Повторный запуск setup приводит
существующий event destination к этим четырём.

Письмо ищется по SES message ID, сохранённому в `provider_message_id`. Если запись не найдена,
событие не меняет данные.

`magicpro:aws-status` завершается кодом ошибки, если хоть одна секция упала, —
остальные секции при этом всё равно показываются.

## Контрольный сценарий после настройки

1. Убедитесь, что новый project key активен, а старый выведен из эксплуатации осознанно.
2. Перенесите переменные в фактически загружаемое окружение и удалите файл результата.
3. Если используется config cache, пересоберите его после правки `.env`.
4. Запустите `magicpro:aws-status` и проверьте каждую секцию.
5. В AWS Console проверьте подтверждённую HTTPS-подписку нужного topic.
6. Отправьте тестовое письмо тем же путём, которым работает production: API, SMTP и/или очередь.
7. Проверьте наличие `provider_message_id` у записи письма.
8. Дождитесь `Delivery` и убедитесь, что статус изменился.
9. Проверьте журналы на `AWS SNS signature rejected`, ошибки подтверждения и неизвестные письма.
10. Отдельно проверьте bounce/complaint на тестовых адресах SES, если это допустимо в окружении.

## Если настройка оборвалась

Команда не выполняет rollback. После ошибки могут остаться частично созданные или изменённые
ресурсы. Проверьте вручную:

- активные ключи IAM user;
- содержимое inline policy;
- SNS topic и policy;
- HTTPS-подписки;
- configuration set и event destination;
- наличие и права файла результата;
- работоспособность прежнего почтового канала.

Повторный запуск в основном переиспользует именованные ресурсы и обновляет policy/destination,
но выпуск access key требует особой осторожности.

## Официальные материалы AWS и Laravel

- [IAM access keys: ограничение и ротация](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html)
- [Получение SES SMTP credentials](https://docs.aws.amazon.com/ses/latest/dg/smtp-credentials.html)
- [SES и регионы](https://docs.aws.amazon.com/ses/latest/dg/regions.html)
- [Проверка подписи SNS](https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message.html)
- [Жизненный цикл SNS-подписки](https://docs.aws.amazon.com/sns/latest/dg/sns-delete-subscription-topic.html)
- [IAM security best practices](https://docs.aws.amazon.com/IAM/latest/UserGuide/best-practices.html)
- [Laravel configuration и config cache](https://laravel.com/framework/docs/13.x/configuration)
