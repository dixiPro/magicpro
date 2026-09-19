# ТЗ: разложить маршруты по файлам

Сейчас все маршруты пакета, кроме МСП, лежат в `admin/web.php` — один файл на
270 строк: админка, её API, публичный вебхук Amazon, adminer и в самом конце
динамический маршрут статей. Имя файла говорит «админка», содержимое — нет.

Провайдер подключает их так:

```php
$this->loadRoutesFrom(__DIR__ . '/Mcp/ai.php');          // МСП, до catch-all
Route::middleware('web')->group(function () {
    $this->loadRoutesFrom(__DIR__ . '/../admin/web.php'); // всё остальное
});
```

## Что есть сейчас

### Админка: страницы

| Адрес                           | Что открывает                            |
| ------------------------------- | ---------------------------------------- |
| `GET /a_dmin`                   | главная админки                          |
| `GET /a_dmin/documentation`     | документация                             |
| `GET /a_dmin/documentationFile` | исходный markdown страницы, `magic.auth` |
| `GET /a_dmin/mcp`               | токен МСП и архив агента                 |
| `GET /a_dmin/mcpInstall`        | `install.zip`, `magic.auth`              |
| `GET /a_dmin/other`             | витрина иконок                           |
| `GET /a_dmin/setup`             | настройки                                |
| `GET /a_dmin/import_tab`        | импорт                                   |
| `GET /a_dmin/laravelUsers`      | пользователи сайта                       |
| `GET /a_dmin/mailSystem`        | почта                                    |
| `GET /a_dmin/cron`              | крон                                     |
| `GET /a_dmin/feed`              | ленты                                    |
| `GET /a_dmin/artList`           | список статей                            |
| `GET /a_dmin/artEditor`         | редактор статьи                          |
| `GET /a_dmin/fileManager`       | файловый менеджер                        |
| `GET /a_dmin/crawler`           | паук                                     |
| `GET /a_dmin/adminList`         | админы                                   |
| `GET /a_dmin/phpinfo`           | phpinfo, `magic.auth`, без CSRF          |
| `ANY /a_dmin/adminer`           | adminer, `magic.auth`, без CSRF (перенесён с `/a_shop/adminer` 2026-09-16) |

### Админка: API (все `POST`, все `magic.auth`, все с CSRF)

| Адрес                      | Кто обрабатывает                              |
| -------------------------- | --------------------------------------------- |
| `/a_dmin/api/mcpToken`     | `API_McpToken`                                |
| `/a_dmin/api/laravelUsers` | `API_Auth`                                    |
| `/a_dmin/api/mailSystem`   | `API_Mail`                                    |
| `/a_dmin/api/cron`         | `API_Cron`                                    |
| `/a_dmin/api/feed`         | `API_Feeds`                                   |
| `/a_dmin/api/import`       | `API_Import`                                  |
| `/a_dmin/api/cleanup`      | `API_Cleanup`                                 |
| `/a_dmin/api/articles`     | `API_ArticlesPostController`                  |
| `/a_dmin/api/fileManager`  | `API_FileManagerPostController`               |
| `/a_dmin/api/editUsers`    | `API_EditUsersController`, `magic.auth:admin` |
| `/a_dmin/api/setup`        | `API_Setup`, `magic.auth:admin`               |

Плюс три `GET`, которые отдают файлы и отчёты: `/a_dmin/api/clearCache`,
`/a_dmin/api/cleanupReport`, `/a_dmin/api/exportArticle`.

### Вход в админку

| Адрес                 | Что делает                            |
| --------------------- | ------------------------------------- |
| `POST /a_dmin/login`  | вход, форма стоит в шаблоне админки   |
| `POST /a_dmin/logout` | выход                                 |
| `GET /login`          | штатный логин Laravel уводится на `/` |

### Публичное

| Адрес                               | Что делает                                           |
| ----------------------------------- | ---------------------------------------------------- |
| `POST /awsHook`                     | вебхук SES/SNS, без CSRF, подпись проверяется внутри |
| `/mcp/magicpro` (`ai.php`)          | МСП по https, `TokenMiddleware`                      |
| `Mcp::local('magicpro')` (`ai.php`) | МСП локально, stdio                                  |

### Динамический маршрут

В конце файла: сборка `$pattern` из `EXCLUDED_ROUTES` и

```php
Route::any('{any?}', [DynamicRouteHandler::class, 'handle'])
    ->where('any', $pattern)
    ->withoutMiddleware([$csrf]);
```

## Как разложить

Решено: четыре файла в папке `routes/` в корне пакета, рядом с `src/`, `admin/`
и `docs/`. Так принято в Laravel, а `src/` — для классов. Нынешние
`admin/web.php` и `src/Mcp/ai.php` уходят туда.

| Файл                 | Что кладём                                                                          |
| -------------------- | ----------------------------------------------------------------------------------- |
| `routes/admin.php`   | все `/a_dmin/*` — страницы и API, вход и выход админки, `/login`, `/a_dmin/adminer` |
| `routes/site.php`    | публичные адреса пакета: `/awsHook`                                                 |
| `routes/mcp.php`     | содержимое нынешнего `ai.php`: МСП по https и локально                              |
| `routes/dynamic.php` | сборка `$pattern` и catch-all статей                                                |

МСП отдельным файлом: это целая подсистема со своим фасадом `Mcp::` и своей
защитой по токену, у неё два входа (https и `mcp:start`).

Провайдер подключает по порядку:

```php
// the order is part of the contract: dynamic.php goes last,
// the catch-all takes every address registered after it
$this->loadRoutesFrom(__DIR__ . '/../routes/mcp.php');       // outside `web`

Route::middleware('web')->group(function () {
    $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
    $this->loadRoutesFrom(__DIR__ . '/../routes/site.php');
    $this->loadRoutesFrom(__DIR__ . '/../routes/dynamic.php');
});
```

`mcp.php` — **вне** группы `web`, как сейчас `ai.php`: агенту не нужны сессия и
куки, а CSRF отбил бы его POST-запросы.

В шапке `dynamic.php` первой строкой: подключается последним.

`$csrf` (совместимость Laravel 12/13) нужен в `admin.php`, `site.php` и
`dynamic.php`; вычислять его один раз — в провайдере или маленьким методом в
`MagicGlobals`, — а не копировать в каждый файл.

## Проверка после переноса

1. `php -l` для новых файлов.
2. `php artisan route:list` — набор и порядок маршрутов совпадают с прежними.
3. В браузере: страница админки, любой её API, адрес статьи, `/robots___txt`,
   `/mcp/magicpro` (ответ 401 без токена — значит, catch-all его не съел).
4. Адрес несуществующей статьи отдаёт `error404`, а не белый экран.

## Вопросы

1. ~~Три файла или четыре~~ — решено: четыре, в `routes/`.
2. ~~Куда класть adminer~~ — решено: к админке, адрес уже перенесён на
   `/a_dmin/adminer`.
3. ~~Автосбор исключений~~ — решено: не нужен. Laravel перебирает маршруты в
   порядке регистрации, и всё, что зарегистрировано раньше `dynamic.php`, и так
   побеждает catch-all. `EXCLUDED_ROUTES` нужен для маршрутов, которые
   регистрируются позже (чужие пакеты и приложение), а их автосбор увидеть не
   может. Остаётся ручным списком.

## Сделано 2026-09-16

Четыре файла в `routes/`, старые `admin/web.php` и `src/Mcp/ai.php` убраны,
CSRF-middleware — `MagicGlobals::csrfMiddleware()`, провайдер подключает в
описанном порядке. Проверено в браузере: админка и её API, adminer, главная,
`/robots.txt`, несуществующая статья (404), МСП без токена (401), `/awsHook`
без подписи (403). Дока переведена на новые пути.
