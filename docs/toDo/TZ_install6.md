# ТЗ: инсталлер

Итог. Заменяет `TZ_install4.md` и `TZ_install5.md`, вопросы из них учтены.

## Общее

- Язык установки только английский: и сообщения на экран, и лог.
- Лог — свой файл `storage/logs/install.log`, без ротации, не удаляется.
- Пока один класс, дальше посмотрим: `MagicProSrc\Install\Installer`. Поля,
  методы проверки и алгоритм — в нём.
- `AdminController` его только зовёт и рисует `msgArr`. Из контроллера уезжают
  `install()`, все четыре `install*`, `imageTools()`, `cron()`, `stepOk()`,
  `stepFailed()`.
- Сообщения копятся в markdown, на экран уходят одним куском.

## Данные

- `MAGIC_INSTALL_FILE` — константа в `MagicGlobals`, файл
  `storage/app/private/magic/.install`. Установка пройдена, если файл есть и в
  нём `install`.
- `$msgArr = []` — сообщения в markdown. На выходе `implode("\n\n", $msgArr)` и
  `Str::markdown()`.

## Служебные методы

`writeLog(string $msg)`

- Пишет строку в `install.log`, время подставляет само.

## Методы проверки

Общее для всех:

- пишут в лог всё, и успех, и ошибку;
- при ошибке кладут сообщение в `msgArr` в markdown и бросают исключение.

`testCreateWriteDirectory(string $absDir)`

- Нет каталога — создаёт.
- Пишет тестовый файл, удаляет.
- Не вышло — исключение.

`testDirectory(string $absDir)`

- Только наличие каталога.

`checkAdmin()`

- Есть ли хоть один пользователь в `magicPro_users`.
- Нет — `msgArr[] = 'run php artisan magicpro:admin'` и исключение.

`checkDirectories()`

Через `testCreateWriteDirectory`:

```
MAGIC_DATA_DIR
MAGIC_VIEW_DIR
MAGIC_CONTROLLER_DIR
public_path(PUBLIC_UPLOAD_DIR)
VENDOR_PUBLIC
STATIC_HTML_CREATE_DIR
```

Через `testDirectory`:

```
VENDOR_FROM
```

`PUBLIC_UPLOAD_DIR` хранится как путь от `public`, поэтому оборачивается в
`public_path()`.

`checkSymLink()`

- `public/storage`: проверяет и наличие, и что цель читается.
- Битая ссылка — ошибка: `is_link()` на ней отвечает `true`, а картинки не
  отдаются.
- В сообщении команда `php artisan storage:link`.

`checkArticles()`

- Таблица `articles`. Нет — исключение с `php artisan migrate`, без текста
  SQL-ошибки.
- Статья `id = 1`. Нет — создаёт моделью `Article`.
- Создаёт статью с именем по текущей дате и времени, проверяет, что появились
  блейд и контроллер.
- Удаляет её и проверяет, что файлы ушли.
- Любая осечка — сообщение в `msgArr` и исключение.

`checkAssets()`

- Версия из `VENDOR_PUBLIC/version.txt` против `MAGIC_VERSION`
  (`src/Config/version.php`).
- Ассетов нет или версия не совпала — копирует `readyBundle`, переписывает
  `version.txt`.
- Ошибка — исключение.

## Методы без исключений

`checkEnvironment()`

- `cwebp`, `vips` — по коду возврата, не по выводу.
- Расширение `gd`.
- Крон — по свежести отметки `Heartbeat`, порог 120 секунд.
- Ошибки только в `msgArr`, с готовыми командами установки. Исключений не
  бросает.

## Алгоритм

```
если MAGIC_INSTALL_FILE не содержит install {

    try {
        msgArr[] = '## Start installation'
        writeLog('-------- Start installation')

        checkAdmin()
        checkDirectories()
        checkSymLink()
        checkArticles()
        перегенерация всех статей

        записать в MAGIC_INSTALL_FILE 'install'
        msgArr[] = 'installation success'
        writeLog('installation success')
    }
    catch {
        обработать msgArr
        writeLog('installation error aborted')
        return
    }
}

try {
    checkAssets()
}
catch {
    обработать msgArr
    writeLog('assets error aborted')
    return
}

checkEnvironment()
```

- Симлинк проверяется до перегенерации статей.
- Ассеты останавливают всё: без них админка работает неправильно, показывать
  окружение смысла нет.
- Файл установки есть — проверяются только ассеты и окружение.

## Правки вне инсталлера

Отдельные задачи, в класс не входят.

- `create_magicPro_users_table.php`: убрать `readline()` и печать пароля.
  Миграция только создаёт таблицу. Сейчас неинтерактивный `migrate --force`
  заводит админа с пустым паролем.
- Команда `magicpro:admin` — её ещё нет, а `checkAdmin()` на неё ссылается.
  См. пояснение в конце.
- `create_magicPro_articles_table.php`: у `root` и `error404` полный
  `routeParams`, как у `index`. Сейчас `{}`.
- `admin/views/artList.blade.php`: нет ключа `useController` — это `false`.
  Ошибка — только если `routeParams` не разобрался в массив, и подсказка своя,
  про параметры маршрута.
- Пути картинок перенести в `MagicGlobals`: `FeedPathGenerator::$prefix`
  (`magicFeed/`) и `ImageJob::DIR` (`magic/images`).

## Приёмка

- Пустая база: сообщение про `migrate`, без `SQLSTATE`. Прогнали, обновили —
  установка идёт дальше.
- `chown root` на `storage/dataMagicPro`: сообщение про права, генерация не
  запускается, файл установки не пишется.
- Удалить `.install` — установка прошла заново, лишнего не сделала.
- Второй заход подряд — только ассеты и окружение.
- После установки в базе нет статьи с именем по дате, а в `MAGIC_VIEW_DIR` нет
  её блейда.
- `migrate --force` неинтерактивно — без вопросов и без пустого админа.

## php artisan magicpro:admin

Пояснение к команде из раздела правок.

- Обычная artisan-команда пакета: `src/Console/AdminCommand.php`, пространство
  `MagicProSrc\Console` — оно уже в автозагрузке.
- Параметров нет. Email и пароль вводятся руками: `ask()` и `secret()`.
  `secret()` не показывает ввод, и печатать пароль потом нечего.
- Нет терминала — команда завершается с ошибкой и текстом, что запускать её надо
  вручную. Ничего не создаёт.
- Проверяет формат email, уникальность и длину пароля. Пароль через `Hash::make`,
  роль `admin`.
- Регистрация в `MagicServiceProvider::boot()`:

```php
if ($this->app->runningInConsole()) {
    $this->commands([AdminCommand::class]);
}
```

Сейчас пакет не регистрирует ни одной команды — поэтому `magicpro:install` и не
существует, хотя файл лежит в `src/Installer/`.
