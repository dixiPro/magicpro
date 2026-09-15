# Ключ для настройки AWS

Этим ключом работают `magicpro:aws-setup`, `magicpro:aws-status` и
`magicpro:aws-remove`. Он не имеет отношения к ключу, которым сайт отправляет
почту: тот команда выпускает сама.

Ключ настройки живёт в терминале и вводится руками при каждом запуске. Нигде не
сохраняется.

## Создать пользователя

`IAM → Users → Create user`

Имя, например `magicpro-setup`. Галку `Provide user access to AWS Management
Console` не ставить: этому пользователю в консоли делать нечего.

> Next

## Дать права

На шаге `Set permissions` выбрать `Attach policies directly`, затем
`Create policy` и вкладку `JSON`. Вставить:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "MagicProUsers",
      "Effect": "Allow",
      "Action": [
        "iam:GetUser",
        "iam:CreateUser",
        "iam:DeleteUser",
        "iam:TagUser",
        "iam:PutUserPolicy",
        "iam:ListUserPolicies",
        "iam:DeleteUserPolicy",
        "iam:ListAttachedUserPolicies",
        "iam:DetachUserPolicy",
        "iam:ListAccessKeys",
        "iam:CreateAccessKey",
        "iam:DeleteAccessKey",
        "iam:GetAccessKeyLastUsed"
      ],
      "Resource": "*"
    },
    {
      "Sid": "MagicProMail",
      "Effect": "Allow",
      "Action": [
        "ses:GetAccount",
        "ses:GetEmailIdentity",
        "ses:ListEmailIdentities",
        "ses:CreateConfigurationSet",
        "ses:GetConfigurationSet",
        "ses:CreateConfigurationSetEventDestination",
        "ses:GetConfigurationSetEventDestinations",
        "ses:UpdateConfigurationSetEventDestination",
        "sns:CreateTopic",
        "sns:ListTopics",
        "sns:GetTopicAttributes",
        "sns:SetTopicAttributes",
        "sns:Subscribe",
        "sns:Unsubscribe",
        "sns:ListSubscriptionsByTopic"
      ],
      "Resource": "*"
    }
  ]
}
```

Имя политики, например `magicpro-setup-policy`.

> Next → Create user

**Почему не `AdministratorAccess`.** Он работает, и раньше здесь было написано
именно так. Но это ключ, который лежит у тебя на машине и вводится в терминал:
если он утечёт, разница между «может настроить почту» и «может всё в аккаунте»
— это разница между испорченной рассылкой и потерянным аккаунтом. Список выше —
ровно то, что команды вызывают, ни одним действием больше.

Сузить `"Resource": "*"` до конкретных пользователей и топиков можно, но их
имена берутся из `aws-setup.ini` и меняются от сайта к сайту, поэтому по
умолчанию оставлено так.

## Выпустить ключ

`View user` → `Create access key` → `Command Line Interface (CLI)` →
`Create access key`.

Access key и Secret access key показываются **один раз** — сохранить сразу.

## После работы

Ключ настройки нужен, пока настраиваешь. Закончил — удали его:
`IAM → Users → magicpro-setup → Security credentials → Delete`. Понадобится
снова — выпустишь новый за минуту.

Долгоживущий ключ с правами на IAM — это то, что ищут в чужих репозиториях и
историях команд.
