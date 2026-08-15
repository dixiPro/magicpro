# Установка

Инструкция для того, кто ставит MagicPro. Как это устроено внутри — `inside.md`.

Нужен Laravel 13 и PHP 8.3. Для картинок пригодятся `cwebp` и `libvips`, но без
них система поднимется.

## Четыре команды

```bash
composer require dixipro/magicpro
php artisan migrate
php artisan magicpro:admin
(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd $(pwd) && /usr/bin/php artisan schedule:run >> /dev/null 2>&1") | sort -u | sudo crontab -u www-data -
php artisan storage:link
```

`magicpro:admin` спросит email и пароль администратора. Крон нужен для отложенных
писем.

Порядок значения не имеет: что забыли, админка попросит.

## Дальше открыть /a_dmin

Тут и происходит установка: создаются каталоги, стартовые статьи и их файлы,
копируются ассеты. Занимает секунду.

Дальше — форма входа, email и пароль от `magicpro:admin`.

## Если на странице сообщение

Значит чего-то не хватает. В каждом сообщении есть команда — выполнить её и
обновить страницу.

Чаще всего это одно из трёх:

| Сообщение | Что делать |
| --- | --- |
| `No table of articles` | `php artisan migrate` |
| `No admin to log in with` | `php artisan magicpro:admin` |
| `Cannot write to …` | `sudo chown -R :www-data` на указанный каталог |

Установка не запомнится, пока не пройдёт целиком, так что чинить можно в любом
порядке: выполнили — обновили страницу.

Пусто на странице — всё в порядке.

## Если что-то сломалось потом

- Пропали страницы сайта — `/a_dmin/setup`, ссылка «Перегенерировать статьи».
- Прогнать установку заново — удалить `storage/app/private/magic/.install` и
  открыть `/a_dmin`.
- Что происходило и когда — `storage/logs/install.log`.

## Обновление

```bash
composer update dixipro/magicpro
php artisan migrate
```

Ассеты админки обновятся сами при первом заходе в `/a_dmin`.
