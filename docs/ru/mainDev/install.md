# Установка

Инструкция для того, кто ставит MagicPro. Внутренний порядок работы — в [описании Installer](use.md#установка-и-восстановление-согласованности).

Нужен Laravel 13 и PHP 8.3. Для картинок пригодятся `cwebp` и `libvips`, но без
них система поднимется.

## Команды установки

```bash
composer require dixipro/magicpro
php artisan migrate
php artisan magicpro:admin
(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd $(pwd) && /usr/bin/php artisan schedule:run >> /dev/null 2>&1") | sort -u | sudo crontab -u www-data -
php artisan storage:link
```

`magicpro:admin` спросит email и пароль администратора. Крон нужен для отложенных
писем.

Выполняйте команды последовательно; если один из шагов пропущен, админка
покажет соответствующее сообщение.

## nginx

Статические копии страниц краулер публикует в `public/html/` (настройка
`STATIC_HTML_DIR`), и отдавать их должен nginx — раньше php. Без этих строк
копии просто лежат, а каждый запрос идёт в Laravel.

```nginx
server {
    listen 80;
    server_name example.test;

    root /home/site/public;
    index index.php index.html;

    client_max_body_size 125M;

    # Serve the static homepage when present, otherwise use PHP.
    location = / {
        try_files /html/index.html /index.php$is_args$args;
    }

    # Try the static page, a real file, a directory, then PHP.
    location / {
        try_files /html$uri.html $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $document_root;
        fastcgi_read_timeout 300s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Путь `/html` в `try_files` должен совпадать с `STATIC_HTML_DIR` без `public`:
поменяли настройку — меняйте и конфиг.

Успешная публикация файлов статьи снимает её собственную HTML-копию.
Границы обновления и порядок обхода описаны в [статической публикации](../mainUse/static.md).

## Дальше открыть /a_dmin

Тут и происходит установка: создаются каталоги, стартовые статьи и их файлы,
копируются ассеты.

Дальше — форма входа, email и пароль от `magicpro:admin`.

## Если на странице сообщение

Значит чего-то не хватает. В каждом сообщении есть команда — выполнить её и
обновить страницу.

Чаще всего это одно из трёх:

| Сообщение                 | Что делать                                     |
| ------------------------- | ---------------------------------------------- |
| `No table of articles`    | `php artisan migrate`                          |
| `No admin to log in with` | `php artisan magicpro:admin`                   |
| `Cannot write to …`       | `sudo chown -R :www-data` на указанный каталог |

Установка не запомнится, пока не пройдёт целиком, так что чинить можно в любом
порядке: выполнили — обновили страницу.

Ниже сообщений идёт список «Проверено» — что смотрели и чем оно ответило: версия
ассетов, `cwebp` и `vips` с версиями, php-расширения, время последней отметки
крона. Нет сообщений и есть этот список — всё в порядке.

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
