# AWS: как пользоваться

## Что нужно до

Домен и адрес отправки подтверждены в SES: `Verified`, DKIM `SUCCESS`. Это
записи в DNS, их публикует человек, командам там делать нечего.

Отдельный ключ AWS с правами на настройку. Не корневой ключ аккаунта и не ключ
проекта — свой, для этой работы:

```text
iam: CreateUser, TagUser, PutUserPolicy, CreateAccessKey, UpdateAccessKey, ListAccessKeys, GetUser
ses: GetEmailIdentity, GetAccount, ListEmailIdentities, CreateConfigurationSet, GetConfigurationSet,
     CreateConfigurationSetEventDestination, UpdateConfigurationSetEventDestination,
     GetConfigurationSetEventDestinations
sns: CreateTopic, ListTopics, SetTopicAttributes, Subscribe, Unsubscribe, ListSubscriptionsByTopic
```

Ключ не хранится нигде: команда спрашивает его в терминале каждый раз. Секрет
вводится скрыто. Без терминала команда работать откажется.

## Файл настроек

Один на сайт, в корне проекта, из него читают все три команды. Секретов в нём
нет, можно держать в git.

```ini
; aws-setup.ini
region    = eu-north-1
domain    = dixipro.net
user      = magicpro-dixipro
smtp_port = 587
webhook   = https://dixipro.net/awsHook
```

`webhook` нужен одной `magicpro:aws-webhook`; сайту, которому события не нужны,
эта строка ни к чему.

Имена ресурсов AWS не пишутся: они считаются из `user`, и все три команды считают
одинаково.

```text
policy            {user}-policy
topic             {user}-events
config_set        {user}
event_destination {user}-sns
```

Отсюда правило: **переименовал `user` — команды пошли искать другие ресурсы**.
Имя меняется вместе с ресурсами, а не отдельно от них.

Свой сайт — свой файл. Звать удобно по домену, путь передаётся флагом:

```bash
php artisan magicpro:aws-setup   --file=aws-dixipro.ini
php artisan magicpro:aws-webhook --file=aws-dixipro.ini
php artisan magicpro:aws-status  --file=aws-dixipro.ini
```

## Настроить отправку

```bash
php artisan magicpro:aws-setup
```

Заводит пользователя, права и ключ — то, чем сайт шлёт письма. Ни топика, ни
подписки, ни набора конфигурации не создаёт: это дело второй команды, и сайту,
которому события не нужны, хватает одной этой.

Спросит ключ настройки, проверит домен и предупредит перед выпуском нового
ключа:

```text
A new Access Key will be issued, every earlier key of the user is deactivated.
Mail stops going out until the new key reaches .env_mpro.
Continue? (yes/no) [no]:
```

Это не формальность. Прежние ключи пользователя выключаются сразу, а `.env_mpro`
правится потом руками — между этими моментами письма не уходят. Запускать на
боевом сайте нужно тогда, когда есть минута дописать файл.

На выходе — `aws-{домен}-{дата}.result`, права `600`, строка `*.result`
дописывается в `.gitignore`. Внутри блок для `.env_mpro`:

```text
AWS_SesV2Client=true
AWS_ACCESS_KEY_ID=AKIA...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=eu-north-1

MAIL_MAILER=smtp
MAIL_HOST=email-smtp.eu-north-1.amazonaws.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=AKIA...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=info@dixipro.net
MAIL_FROM_NAME="magicpro-dixipro"

# IAM user:      magicpro-dixipro
# webhookUrl =   'https://dixipro.net/awsHook'
```

Ключ выпущен в одном регионе и работает только в нём, поэтому `AWS_DEFAULT_REGION`
едет вместе с ним. `MAIL_FROM_NAME` — имя пользователя из ini; читаемое имя
отправителя вписывается руками, это подпись, а не настройка AWS.

Строки после пустой — комментарии, в `.env_mpro` они не нужны. Адрес вебхука
записан для справки: у MagicPro путь всегда `/awsHook`, и через полгода его не
придётся искать в исходниках.

Скопировать верхнюю часть в `.env_mpro` и удалить файл. Это единственное место,
где записан секрет: на экран он не печатается, в лог не пишется.

Секрет отдаётся Амазоном один раз, поэтому файл пишется, даже если команда упала
на следующем шаге.

## Настроить события

```bash
php artisan magicpro:aws-webhook
```

Первым делом команда стучится в адрес постом и ждёт ответа самого обработчика,
`{"status": true}`. Не ответил — останов до того, как что-либо создано в AWS:

```text
[ERROR] answered, but not by the MagicPro hook: https://dixipro.net/awsHook
POST /awsHook has to reach AwsHookHandler: check that the dynamic router lets it through.
```

Это ровно тот случай, который иначе стоит трёх суток: SNS подтверждает подписку
таким же стуком, и если стучаться некуда, подписка висит `PENDING`, а команда
рапортует об успехе по всем остальным шагам. Обычная причина — динамический
роутер сайта перехватил `/awsHook`; вторая по частоте — в ini остался чужой
адрес.

Та же команда и заводит всё с нуля, и меняет адрес: топик, разрешение для SES
публиковать в него, подписка на адрес из `webhook`, набор конфигурации с
восемью событиями. Чего нет — создаётся, что есть — приводится к нужному виду.
Прочие https-подписки отписываются: топик обслуживает один сайт.

`CONFIRMED` — готово. `PENDING` — SNS постучался, а сайт не ответил: адрес
недоступен снаружи, или это другой сервер. Подтверждение принимает сам MagicPro,
`POST /awsHook`, вручную ничего подтверждать не надо.

В конце команда печатает строку для `.env_mpro`:

```text
AWS_SES_CONFIGURATION_SET=magicpro-dixipro
```

Секрета в ней нет, поэтому она идёт на экран, а не в файл. Без неё SES не шлёт
событий вовсе — и это ровно то, что нужно проекту, которому вебхук не нужен:
не запускал вторую команду, значит и строки нет.

Сменить адрес потом — правишь `webhook` в файле и запускаешь ту же команду.

## Посмотреть, что есть

```bash
php artisan magicpro:aws-status
```

Показывает домен и DKIM, песочницу и квоты, список всех identity аккаунта, топик
со всеми подписками, набор конфигурации с событиями и — последним разделом — что
об этом знает сам проект: `AWS_SesV2Client`, `AWS_SES_CONFIGURATION_SET`,
`MAIL_HOST`.

Последний раздел важнее, чем кажется. Правильная настройка в AWS и `.env_mpro`,
который о ней не знает, выглядят снаружи одинаково: письма уходят, события не
приходят.

Разделы независимы: не хватило прав на SNS — SES всё равно покажется.

## Порядок с нуля

1. Домен и адрес в SES, DNS, дождаться `Verified` и DKIM.
2. Выход из песочницы — заявка в поддержку AWS.
3. `aws-setup.ini`.
4. `php artisan magicpro:aws-setup`.
5. Скопировать блок в `.env_mpro`, удалить `.result`. Письма уже уходят.
6. Нужны события: строка `webhook` в том же файле,
   `php artisan magicpro:aws-webhook`, строку с набором конфигурации — в
   `.env_mpro`.
7. `php artisan magicpro:aws-status` — убедиться.
