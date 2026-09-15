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

Ключ не хранится нигде: команда спрашивает его в терминале каждый раз. И ключ,
и секрет вводятся скрыто — на экране не видно ни того, ни другого. Без терминала
команда работать откажется.

## Файл настроек

Один на сайт, в корне проекта, из него читают обе команды. Секретов в нём нет,
можно держать в git.

```ini
; aws-setup.ini
region    = eu-north-1
domain    = dixipro.net
user      = magicpro-dixipro
smtp_port = 587
webhook   = /awsHook
```

`webhook` — путь, а не адрес: домен берётся из `domain`, схема всегда `https`.
Строку можно не писать вовсе, у MagicPro путь всегда `/awsHook`. Целый адрес
тоже принимается — для сайта, который живёт не на том домене, с которого шлёт:
`webhook = https://other.example/awsHook`.

Имена ресурсов AWS не пишутся: они считаются из `user`, и обе команды считают
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
php artisan magicpro:aws-setup  --file=aws-dixipro.ini
php artisan magicpro:aws-status --file=aws-dixipro.ini
```

## Настроить

```bash
php artisan magicpro:aws-setup
```

Одна команда делает всё: пользователя, права и ключ — то, чем сайт шлёт письма, —
и топик, подписку, набор конфигурации — то, чем сайт узнаёт, что с письмами
стало.

### Проверка настроек

Первым делом команда показывает, что прочитала, и ждёт подтверждения. Настройки
пишутся руками и читаются через полгода: устаревший регион или переименованный
`user` ловятся здесь, а не потом — кучей ресурсов не с теми именами в консоли
AWS.

```text
Settings: /var/www/site/aws-setup.ini

Settings of the site:
  region            eu-north-1
  domain            dixipro.net
  user              magicpro-dixipro
  smtp_port         587
  webhook           /awsHook

Will be created in AWS or brought to this shape:
  IAM user          magicpro-dixipro
  policy            magicpro-dixipro-policy
  SNS topic         magicpro-dixipro-events
  configuration set magicpro-dixipro
  event destination magicpro-dixipro-sns
  webhook           https://dixipro.net/awsHook

Continue? (yes/no) [no]:
```

Дальше спрашивается ключ настройки и проверяется домен: не подтверждён или DKIM
не `SUCCESS` — останов, ничего не создано.

### Ключ

Перед выпуском ключа — второй вопрос:

```text
A new Access Key will be issued, every earlier key of the user is deactivated.
Mail stops going out until the new key reaches .env_mpro.
No — the key is left alone and only the events are set up.
Issue a new key? (yes/no) [no]:
```

Это не формальность. Прежние ключи пользователя выключаются сразу, а `.env_mpro`
правится потом руками — между этими моментами письма не уходят. Запускать на
боевом сайте нужно тогда, когда есть минута дописать файл.

Ответ «нет» — не отмена запуска: ключ остаётся прежним, а события настраиваются.
Это и есть способ поменять адрес вебхука, ничего не ломая.

### События

Топик, разрешение для SES публиковать в него, набор конфигурации с восемью
событиями создаются всегда: сайт для этого не нужен. Чего нет — создаётся, что
есть — приводится к нужному виду.

Подписка — другое дело, ей сайт нужен. Команда стучится в адрес постом и ждёт
ответа самого обработчика, `{"status": true}`:

```text
[ERROR] answered, but not by the MagicPro hook: https://dixipro.net/awsHook
POST /awsHook has to reach AwsHookHandler: check that the dynamic router lets it through.
Not subscribed. Run the command again when the address answers.
```

Не ответил — подписки нет, всё остальное на месте. Это не поломка: на новом
домене сайта обычно ещё нет, а настройка AWS уже нужна. Поднялся сайт — тот же
запуск, и адрес подпишется.

Стук нужен потому, что SNS подтверждает подписку таким же стуком. Стучаться
некуда — подписка висит `PENDING` трое суток, а команда рапортует об успехе по
всем остальным шагам. Обычная причина — динамический роутер сайта перехватил
`/awsHook`; вторая по частоте — в ini остался чужой адрес.

`CONFIRMED` — готово. `PENDING` — SNS постучался, а сайт не ответил: адрес
недоступен снаружи, или это другой сервер. Подтверждение принимает сам MagicPro,
`POST /awsHook`, вручную ничего подтверждать не надо.

Прочие https-подписки отписываются: топик обслуживает один сайт.

### Файл результата

`aws-{домен}-{дата}.result`, права `600`, строка `*.result` дописывается в
`.gitignore`. Внутри блок для `.env_mpro`:

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
# webhook:       https://dixipro.net/awsHook  CONFIRMED

# Set the webhook up at the address above, then uncomment the line below:
# AWS_SES_CONFIGURATION_SET=magicpro-dixipro
```

Ключ выпущен в одном регионе и работает только в нём, поэтому `AWS_DEFAULT_REGION`
едет вместе с ним. `MAIL_FROM_NAME` — имя пользователя из ini; читаемое имя
отправителя вписывается руками, это подпись, а не настройка AWS.

Строки после пустой — комментарии. Верхний блок копируется в `.env_mpro` как
есть, и письма уходят.

`AWS_SES_CONFIGURATION_SET` записан закомментированным намеренно. Эта строка —
выключатель событий: с ней SES шлёт их в топик, без неё молчит. Пока вебхук по
указанному адресу не отвечает, включать нечего, поэтому снимает комментарий
человек — тогда, когда сайт поднят и подписка `CONFIRMED`.

Скопировать нужное в `.env_mpro` и удалить файл. Это единственное место, где
записан секрет: на экран он не печатается, в лог не пишется. Секрет отдаётся
Амазоном один раз, поэтому файл пишется, даже если команда упала на следующем
шаге.

Ключ не выпускался — файла нет и не нужно: секрета в этом прогоне не появилось.
Строки для `.env_mpro` печатаются на экран.

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

Адрес хука спрашивать не надо: он напечатан на стартовой странице админки, в
списке проверенного, и там же сказано, отвечает ли он. Проверка стучится тем же
постом, что и сама команда перед подпиской. В ini от него нужен только путь —
домен там уже написан.

## Порядок с нуля

1. Домен и адрес в SES, DNS, дождаться `Verified` и DKIM.
2. Выход из песочницы — заявка в поддержку AWS.
3. `aws-setup.ini`.
4. `php artisan magicpro:aws-setup`.
5. Скопировать верхний блок в `.env_mpro`, удалить `.result`. Письма уже уходят.
6. Нужны события: поднять сайт, чтобы `/awsHook` отвечал, прогнать команду ещё
   раз (ключ можно не выпускать), снять комментарий с
   `AWS_SES_CONFIGURATION_SET`.
7. `php artisan magicpro:aws-status` — убедиться.
