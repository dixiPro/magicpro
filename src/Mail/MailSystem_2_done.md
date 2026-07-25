# Почтовая система изменения

Добавить в базу поле fromName
Я откатил последнюю миграцию
php artisan migrate:rollback

тебе
Добавить поле fromName в таблицу magicPro_mail_messages файл packages\dixipro\magicpro\src\Mail\MailSystemAdmin.md

и в миграцию packages\dixipro\magicpro\database\migrations\create_magicPro_mail_messages_table.php
