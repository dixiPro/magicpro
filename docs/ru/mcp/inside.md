# MCP MagicPro: устройство и сопровождение

Документ предназначен для разработчика, который меняет MCP-сервер, его
инструменты, авторизацию или административный экран. Пользовательский сценарий
и контракты вызовов описаны в `use.md`.

## Границы модуля

Пакет требует `laravel/mcp:^0.9`; во время аудита установлен `laravel/mcp
v0.9.1`. MagicPro регистрирует один класс сервера с двумя транспортами:

```php
Mcp::local('magicpro', MagicProServer::class);
Mcp::web('/mcp/magicpro', MagicProServer::class)
    ->middleware(TokenMiddleware::class);
```

Основные файлы:

| Путь | Ответственность |
| --- | --- |
| `routes/mcp.php` | регистрация local и web transport |
| `src/Mcp/Servers/MagicProServer.php` | инструкции initialize и реестр инструментов |
| `src/Mcp/Tools/*.php` | MCP-контракты и адаптеры к API MagicPro |
| `src/Mcp/Token.php` | выпуск, хранение, проверка, продление и отзыв токена |
| `src/Mcp/TokenMiddleware.php` | авторизация HTTP MCP-запроса |
| `src/Mcp/McpLog.php` | аудит HTTP-запросов |
| `src/Mcp/API_McpToken.php` | команды токена для админки |
| `admin/views/mcp.blade.php` | страница раздела MCP |
| `admin/js/app/mcpToken/mcpToken.vue` | UI токена и URL |
| `src/Docs/DocsTree.php` | индекс документации для `list-docs` |

MCP не содержит собственной бизнес-модели и миграций. Статьи, ленты и файлы он
меняет через существующие API и модели соответствующих модулей.

## Путь запроса

```text
Codex на другой машине
        │ Streamable HTTP + Bearer token
        ▼
POST /mcp/magicpro
        │ Laravel MCP middleware
        ▼
TokenMiddleware → Token → McpLog → magic guard
        │
        ▼
MagicProServer → Tool → API статей / лент / файлов

Codex на машине проекта
        │ stdio
        ▼
php artisan mcp:start magicpro
        │ без TokenMiddleware и McpLog
        └──────────────► тот же MagicProServer
```

`MagicServiceProvider::boot()` загружает `routes/mcp.php` раньше
`routes/dynamic.php`. Порядок существенен: динамический маршрут статей иначе
перехватил бы `/mcp/magicpro`.

Web-маршрут не входит в группу `web`: у него нет сессии и CSRF. Встроенный
регистратор `laravel/mcp` создаёт `POST` transport и отвечает `405` с
`Allow: POST` на `GET` и `DELETE`. После middleware библиотеки к POST добавлен
`TokenMiddleware` MagicPro.

Локальный сервер запускает команда библиотеки:

```bash
php artisan mcp:start magicpro
```

## Идентификация и initialize

`MagicProServer` в конструкторе ставит имя и версию, которые initialize отдаёт
клиенту:

```text
name: MagicPro
version: MAGIC_VERSION      (src/Config/version.php)
```

Раньше они наследовались от библиотеки — `Laravel MCP Server` и `0.0.1`: не
отличить от чужого Laravel MCP и не понять, какой релиз пакета на сайте.

Handle `magicpro`, используемый в конфиге и `mcp:start`, не является этим
полем имени.

Инструкций сервер не шлёт: `instructions = ''` (пустая строка, иначе библиотека
подставит своё умолчание). Правила агента — страница `mcp/agent.md`: `AGENTS.md`
из `install.zip`, который клиент читает при каждом запуске, велит первым делом
взять её `get-doc`. Server instructions хост вставлял бы в каждый запрос — это
лишняя копия и лишние деньги.

## Архив папки агента

`GET /a_dmin/mcpInstall` (`magic.auth`, только `admin`) —
`InstallArchive::build()`:

- заготовка — `docs/mcp-install/` (вне `docs/ru`, чтобы раздел «Документация» не
  показывал её файлы «вне дерева»);
- все файлы заготовки, включая `.mcp.json` и `.codex/`, уходят в zip внутри
  папки `magicpro-mcp-<host>`;
- `AGENTS.md` в архиве — три строки: работать только через МСП, первым делом
  `get-doc ru/mcp/agent.md`, МСП не отвечает — остановиться. Сами правила в
  архив не кладутся: агент читает их с сайта, поэтому они всегда той версии,
  что стоит на сайте, и правка доходит до всех без нового архива;
- метка `__MCP_URL__` в каждом файле заменяется на `url('/mcp/magicpro')`;
- архив собирается во временном файле и удаляется после отдачи
  (`deleteFileAfterSend`), имя — `install.zip`.

`InstallArchive::available()` — есть ли `ZipArchive`; страница MCP без него
показывает «не установлено расширение zip» вместо кнопки.

Только Windows: лаунчеры `.bat`. Архив для Linux — в `toDo/risks/mcp.md`.

## Реестр инструментов

`MagicProServer::$tools` содержит 18 классов. Порядок влияет на порядок выдачи
в `tools/list` и сгруппирован по назначению:

1. сведения о проекте;
2. документация;
3. чтение статей;
4. структура статей;
5. запись статей;
6. ленты;
7. файлы.

| MCP name | Класс | Внутренний вызов |
| --- | --- | --- |
| `get-project-name` | `GetProjectNameTool` | `config('app.name')` |
| `list-docs` | `ListDocsTool` | `DocsTree::pages()` |
| `get-doc` | `GetDocTool` | `MproHelper::getDoc()` |
| `get-article` | `GetArticleTool` | articles `getById` |
| `get-article-by-name` | `GetArticleByNameTool` | articles `articleByName` |
| `search-articles` | `SearchArticlesTool` | articles `search` |
| `get-article-tree` | `GetArticleTreeTool` | articles `makeHeTree` |
| `get-article-children` | `GetArticleChildrenTool` | articles `getChildrens` |
| `get-article-parents` | `GetArticleParentsTool` | articles `getParents` |
| `get-article-siblings` | `GetArticleSiblingsTool` | articles `getBrothers` |
| `create-article` | `CreateArticleTool` | articles `createNew` |
| `save-article` | `SaveArticleTool` | articles `saveById` |
| `move-article` | `MoveArticleTool` | articles `move` |
| `delete-article` | `DeleteArticleTool` | несколько article-команд |
| `feed-api` | `FeedApiTool` | разрешённые команды `API_Feeds` |
| `feed-api-write` | `FeedApiWriteTool` | разрешённые команды `API_Feeds` и safety-обвязка |
| `feed-api-schema` | `FeedApiSchemaTool` | разрешённые команды `API_Feeds`, запрет у ленты с записями |
| `read-file` | `ReadFileTool` | file manager `loadFile` |
| `save-file` | `SaveFileTool` | file manager `saveFile` |
| `list-dir` | `ListDirTool` | file manager `start` (без `path`) и `dirList` |
| `make-dir` | `MakeDirTool` | file manager `mkdir` |

В сервере нет зарегистрированных MCP resources или prompts. Документация
отдаётся именно инструментами `list-docs` и `get-doc`.

## Контракт одного Tool

Инструмент состоит из четырёх частей:

```php
#[Name('get-article')]
#[Description('...')]
class GetArticleTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('...')->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        // Delegate and map the result.
    }
}
```

- `Name` — публичный стабильный идентификатор.
- `Description` — инструкция модели, а не внутренний комментарий. Здесь должны
  находиться read-before-write, destructive confirmation и ограничения.
- `schema()` — JSON Schema аргументов. Общий объект `params` у feed tools не
  проверяет форму каждой команды, поэтому эта проверка остаётся в `API_Feeds`.
- `handle()` — адаптер ответа внутреннего API к MCP.

Успех обычно возвращается через `Response::structured()`. Laravel MCP добавляет
и JSON-текст в `content`, и объект в `structuredContent`. Пустой массив эта
фабрика не принимает, поэтому `FeedApiTool::wrap()` превращает список или
пустоту в `['results' => $data]`.

Ошибка внутреннего API превращается в `Response::error($message)` и в протоколе
имеет `isError: true`. Это не JSON-RPC transport error: соединение остаётся
живым, а ошибкой является конкретный tool result.

## Инструменты документации

### list-docs

`DocsTree::lang()` берёт `MagicGlobals::$INI['LANGUAGE']`. Затем
`DocsTree::pages()` читает `docs/<lang>/index.json`, наследует флаг `agent` от
групп и помечает физически существующие страницы.

`ListDocsTool` оставляет только строки одновременно с `agent = true` и
`exists = true`, возвращая:

```json
{
  "docs": [
    {
      "section": "Сайт: как собирать",
      "name": "Как собирать сайт",
      "about": "статья: имя, адрес, маршрут, Blade, контроллер, сохранение и публикация",
      "path": "ru/mainUse/use.md"
    }
  ]
}
```

Если индекса языка нет или JSON не разобрался, страницы превращаются в пустой
массив и инструмент возвращает ошибку «there is no documentation index...».
Отдельного различения «файл отсутствует» и «JSON повреждён» нет.

### get-doc

Аргумент проверяется регулярным выражением
`<lang>/<segments>.md`. Точка разрешена только перед расширением, поэтому `..`
собрать нельзя. Затем из пути выделяются язык и имя без `.md`, и
`MproHelper::getDoc($name, $lang, false)` возвращает исходный Markdown.

`GetDocTool` отдаёт только страницу, помеченную в индексе её языка
`agent: true` и существующую на диске, — ту же, что предлагает `list-docs`.

## Адаптеры статей

Простые article tools вызывают
`API_ArticlesPostController::run(['command' => ...])`. Его dispatcher ловит
исключения и возвращает массив `status/errorMsg/data/request`; инструмент
отбрасывает служебные ключи и отдаёт только `data`.

Особенности, которые обязаны оставаться в MCP-описаниях:

- `search-articles` оборачивает список в `results`;
- tree/children/parents/siblings оборачивают ответы в одноимённые ключи;
- `create-article` создаёт только запись, а наполнить и сгенерировать файлы
  должен последующий `save-article`;
- `save-article` принимает объект `article`, игнорирует `parentId`, `npp` и
  `directory` и делегирует сохранение, проверку и публикацию в `saveById`;
- `move-article` должен единолично владеть `parentId`, `npp` и флагами
  `directory`;
- `delete-article` добавляет поверх API предварительный breadth-first обход
  поддерева и требует второй вызов с `confirm=true`.

HTTP middleware устанавливает владельца токена через
`Auth::guard('magic')->onceUsingId()`, поэтому версия статьи получает его id.
Локальный stdio-вход guard не устанавливает, и `ArticleArchive` записывает
`userId = 0`.

Текущий порядок `saveById` — обновить модель в транзакции, проверить контроллер
и затем публиковать файлы. Ошибка проверки возвращается как `warning`
в структурированном успешном ответе: адаптер не превращает её в `isError`.
Полный путь и границы отката — в [ядре](../mainDev/use.md#savebyid).
Изменение адаптера проверяйте на duplicate name, ошибке модели, невалидном PHP
и сбое генератора, а не только на успешной записи.

## Адаптеры лент

`feed-api` разрешает только:

```php
['feedsList', 'feedGet', 'itemsList', 'itemGet']
```

`feed-api-write` разрешает только:

```php
['itemCreate', 'itemSave', 'itemDelete']
```

Неизвестная команда отсекается до `API_Feeds::run()`. Для `itemsList` MCP
снижает `perPage` до 250, если клиент передал больше; нижнюю границу, page,
фильтры и сортировку нормализует сам Feed API.

У write tool есть собственная логика:

- `itemDelete` сначала читает запись и holders через внутреннюю команду
  `itemLinks`, формирует preview и только при `confirm` вызывает удаление;
- `itemSave` читает запись и схему и отказывает, если среди переданных кодов
  есть поле `image`.

`itemLinks` наружу отдельной MCP-командой не опубликован. Он используется только
для понятного preview и сообщения об отказе.

`feed-api-schema` разрешает только:

```php
['groupsList', 'groupCreate', 'feedCreate', 'feedSave', 'schemaGet', 'schemaSave']
```

- `feedSave` и `schemaSave` требуют id ленты (`id` / `feedId`) и отказывают,
  если у ленты есть хоть одна запись. Проверка стоит в инструменте, а не в
  `API_Feeds`: админка заполненную ленту менять может. id обязателен, потому что
  API нашёл бы ленту и по коду, мимо проверки.
- `groupCreate` в одном вызове создаёт группу и даёт ей название (`groupCreate`
  + `groupSave` API): в API группа рождается с названием по умолчанию.
- `feedClear`, `feedDelete`, `groupDelete`, `feedMove`, `groupMove` не
  опубликованы.

`API_Feeds::itemSave()` меняет только ключи, присутствующие в `fields`, и
валидирует тоже только их — частичное обновление, так и задумано. Description
инструмента говорит то же: не присланное поле сохраняет значение.

## Файловые инструменты

Оба инструмента напрямую создают `API_FileManagerPostController` и вызывают
`handle()` с командами `loadFile` или `saveFile`. Успешные ответы:

```json
{ "fileData": "..." }
```

```json
{ "status": 1 }
```

Файл должен существовать. Допустимые расширения задаёт
`validateEditExtension()`:

```text
txt rtf csv css js json xml sql md
```

`saveFile` пишет через `file_put_contents()` без временного файла и атомарного
`rename()`, то есть сбой записи может оставить частично записанный файл.

`checkFileInPublicStorageDir()` вычисляет корень через
`realpath(public_path(MagicGlobals::$INI['PUBLIC_UPLOAD_DIR']))`.
Отсутствующий корень вызывает ошибку. До канонизации отвергаются `..`, затем
проверяются существование файла, отсутствие каталога вместо файла и префикс
реального пути с завершающим разделителем. Симлинк наружу не проходит проверку
реального пути. `loadFile` и `saveFile` вызывают эту проверку до чтения и записи.

## HTTP-токен

### Административный API

`POST /a_dmin/api/mcpToken` находится в группе `web`, защищён
`magic.auth`, но исключён из CSRF middleware. `API_McpToken` повторно требует
пользователя guard `magic` с ролью `admin` и поддерживает:

| command | Метод | Ответ `data` |
| --- | --- | --- |
| `state` | `Token::state()` | текущее состояние |
| `issue` | `Token::issue()` | `token` и новое `state` |
| `revoke` | `Token::revoke()` | `revoked` и новое `state` |

Внешняя форма ответа строится `MagicProSrc\Api\AbstractApi`:
`status/errorMsg/data/request`.

### Файл состояния

На пользователя существует один файл:

```text
storage/app/private/magic/mcpTokens/<user_id>.php
```

Содержимое:

```php
return [
    'hash'         => 'sha256...',
    'ip'           => '203.0.113.10',
    'created_at'   => 1789000000,
    'last_used_at' => 1789000000,
];
```

Новый токен строится как `<user_id>___` плюс 32 криптографически случайных
байта в hex. Открытое значение возвращается один раз; на диск записывается
SHA-256. Новый выпуск перезаписывает файл прежнего токена.

Каталог создаётся через `mkdir(..., 0700, true)`, файл после каждой записи
получает `chmod(0600)`. Запись использует `LOCK_EX`, но результат `mkdir`,
`file_put_contents` и `chmod` не проверяется. После записи или перед удалением
вызывается `opcache_invalidate()`.

### Проверка HTTP-запроса

`TokenMiddleware` принимает только заголовок:

```http
Authorization: Bearer <token>
```

`Token::verify()` выполняет проверки в таком порядке:

1. непустое значение;
2. числовой user id до `___` и непустой остаток;
3. наличие файла и `hash_equals()` с SHA-256;
4. точное совпадение `request()->ip()`;
5. не более 3600 секунд после `last_used_at`;
6. не более 86400 секунд после `created_at`.

После успешной проверки middleware **до выполнения метода** вызывает
`Token::touch()`. Поэтому любой принятый HTTP JSON-RPC-запрос, включая
`initialize` и `tools/list`, продлевает idle-окно. Ошибка самого инструмента уже
не отменяет продление.

Затем запрос логируется, и guard получает user id через `onceUsingId()`. Роль и
сам факт существования пользователя повторно не проверяются; возвращаемое
`onceUsingId()` значение игнорируется. Это оставляет токен действующим после
понижения роли или удаления владельца до его обычного истечения.

### Состояние для UI

`Token::state()` возвращает:

```json
{
  "exists": true,
  "ip": "203.0.113.10",
  "created_at": "2026-09-10 10:00",
  "last_used_at": "2026-09-10 10:15",
  "alive": true,
  "idle_left": 59,
  "life_left": 1424
}
```

Остатки округляются вниз до минут. Истёкший файл автоматически не удаляется;
`exists` остаётся true, а `alive` становится false. Пользователь выпускает новый
токен или отзывает старый вручную.

IP зависит от настройки trusted proxies хост-приложения. Неверная настройка
может либо ломать легитимные подключения, либо привязать все токены к адресу
reverse proxy.

## Журнал HTTP MCP

`TokenMiddleware` пишет событие до передачи запроса серверу:

- принятый запрос: `user`, `ip`, JSON-RPC `method`, при наличии — `tool`;
- отказ: `denied`, `ip`, `method`, при наличии — `tool`. Только если токен
  предъявлен: запрос вовсе без `Authorization: Bearer` получает 401 и в журнал
  не попадает — адрес открыт интернету, и сканеры раздували бы журнал.

`method` и `tool` берутся из тела только строкой, в одну строку и не длиннее 100
символов; не строка — пусто. Аргументы и Authorization header не записываются.

`MproHelper::addLog('mcp', ...)` использует `RotatingFileHandler` с 14 файлами.
Фактические имена:

```text
storage/logs/mcp-YYYY-MM-DD.log
```

Локальный stdio transport обходит `TokenMiddleware`, поэтому в этом журнале
его нет. Отдельного application-level аудита после успешного выполнения
инструмента тоже нет: журнал подтверждает запрос, но не его результат.

## Административная страница

GET `/a_dmin/mcp` возвращает `admin/views/mcp.blade.php`. Сам route не имеет
`magic.auth`, но layout и Blade проверяют guard; Vue монтируется только для
admin.

`mcpToken.vue`:

- читает `state` при монтировании;
- перед повторным выпуском и отзывом спрашивает подтверждение;
- держит открытый токен только в `ref` текущей страницы;
- строит URL из `window.location.origin + '/mcp/magicpro'`;
- внизу — подсказка сделать экспорт `root` перед работой агента и таблица
  инструментов (`MagicProServer::toolList()`).

В `admin/js/app/mcpAdmin/`, `admin/js/mcpAdmin.js` и `src/Ai/` остаётся прежний
интерфейс запуска CLI-агента в tmux. Его endpoint `/a_dmin/api/mcp` отключён,
страница грузит новый `mcpToken.js`, но старый Vite entry всё ещё собирается.

## Как добавить или изменить инструмент

1. Найти каноническую бизнес-операцию. Не дублировать запросы к базе в Tool,
   если операция уже есть в API или сервисе.
2. Создать или изменить класс в `src/Mcp/Tools/`.
3. Стабилизировать `#[Name]`: переименование ломает сохранённые клиентские
   политики `enabled_tools`, `disabled_tools` и approvals.
4. В `#[Description]` записать порядок чтение → запись, полную или частичную
   семантику обновления, необратимость и ограничения.
5. Описать JSON Schema. Где возможно, использовать `required`, `enum`,
   `pattern` и конкретные свойства вместо безразмерного object.
6. Превратить ошибку downstream API в `Response::error`, а списки и пустые
   ответы — в непустой объект для `Response::structured`.
7. Добавить класс в `MagicProServer::$tools` в правильную группу.
8. Если правило действует на несколько инструментов, дописать его в
   `docs/ru/mcp/agent.md`; если только на один — в его Description.
9. Обновить `use.md`, реестр в этом файле и число инструментов в
   пользовательских текстах.

## Что проверить после правок

Минимальная матрица должна покрывать оба transport, но опасные проверки лучше
делать в изолированной тестовой базе и временном filesystem:

- `initialize` возвращает name `MagicPro`, версию пакета и пустые instructions;
- `tools/list` содержит ровно зарегистрированные имена и корректные schemas;
- local server запускается `mcp:start magicpro`;
- HTTP без заголовка и с неверным/IP-mismatched/expired токеном получает 401;
- новый токен инвалидирует старый, revoke удаляет доступ;
- удалённый HTTP-вызов получает пользователя guard, local — `userId=0`;
- `list-docs` не выдаёт отсутствующие или `agent=false` страницы, а `get-doc`
  не позволяет читать их обходом;
- каждое article tool проверено на успех и ошибку downstream API;
- move проверен вверх, вниз, первым, последним и между разными родителями;
- preview удаления совпадает с тем, что будет удалено при confirm;
- itemSave проверен на partial/full semantics, required, unique, link и image;
- read/save-file проверены на обычный путь, `..`, symlink и отсутствующий
  корень;
- MCP-журнал не содержит токенов, аргументов и содержимого статей.

На момент аудита специальных тестов MCP в `src/test` нет. Не следует проверять
эти сценарии на рабочем сайте: инструменты записи обращаются к настоящей базе и
файлам.
