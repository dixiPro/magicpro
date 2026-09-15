# Импорт статей: внутреннее устройство

Документ предназначен для разработчика, который меняет import/export, структуру статей или
генерацию рабочих файлов. Пользовательский порядок работы описан в `use.md`.

## Карта компонентов

| Файл | Ответственность |
| --- | --- |
| `src/Import/ImportTree.php` | Парсинг JSON, план, частичный/полный импорт, запуск генерации |
| `src/Import/API_Import.php` | Команды JSON API и маскирование текста файла в ответе |
| `admin/controller/ImportExportController.php` | Экспорт статьи с достижимым поддеревом |
| `admin/controller/MagicProBuilder.php` | Удаление и генерация Blade/PHP-файлов статьи |
| `src/MagicFile.php` | Низкоуровневая запись сгенерированных файлов |
| `admin/js/app/importAdmin/importAdmin.vue` | Выбор файла, mode, check/run, отчёт |
| `admin/js/importAdmin.js` | Vue entrypoint, PrimeVue, i18n |
| `admin/views/import_tab.blade.php` | Blade-shell страницы |
| `admin/web.php` | Страница, import API и export route |
| `database/Models/Article.php` | Casts, defaults и model-level validation статьи |
| `database/migrations/create_magicPro_articles_table.php` | Фактическая DB-схема |

Импорт не имеет собственных таблиц и файлов: его постоянные данные — строки `articles`.

## Внешние точки входа

| Method и path | Обработчик | Защита | Назначение |
| --- | --- | --- | --- |
| `GET /a_dmin/import_tab` | Blade closure | Контент скрыт `@mproauth` | Страница импорта |
| `POST /a_dmin/api/import` | `API_Import::handle()` | `magic.auth`, CSRF | Команды `check` и `run` |
| `GET /a_dmin/api/exportArticle?id=...` | `ImportExportController::exportArticle()` | `magic.auth`, CSRF отключён | Экспорт статьи/поддерева |

`magic.auth` без перечисленных ролей допускает только роль `admin`. Страница не имеет route-level
middleware: неавторизованный запрос получает общий login shell, а авторизованный не-admin может
увидеть интерфейс, но API вернёт 403.

Все маршруты входят в middleware group `web` через `MagicServiceProvider`.

## Таблица `articles`

Импорт пишет через модель `Article`, но удаляет наборы строк bulk query.

| Колонка | Тип миграции | DB default | Import/export |
| --- | --- | --- | --- |
| `id` | bigint auto increment | автоматически | не экспортируется; создаётся заново, кроме root |
| `parentId` | integer, index | `0` | заменён на `parentName` в файле |
| `npp` | integer | `0` | импортируется |
| `name` | string, обычный index | нет | обязательный ключ переноса |
| `title` | string | `''` | импортируется |
| `controller` | text nullable | `null` | импортируется, mutator превращает null в `''` |
| `body` | text nullable | `null` | импортируется, mutator превращает null в `''` |
| `templateName` | string | `''` | не входит в fillable и import/export |
| `directory` | boolean | `false` | импортируется как значение, не вычисляется |
| `menuOn` | boolean | `false` | импортируется |
| `isRoute` | boolean | `false` | импортируется |
| `routeParams` | text nullable | `null` | импортируется, cast `array` |
| `created_at` | timestamp | Laravel/DB | не экспортируется; у новых строк создаётся заново |
| `updated_at` | timestamp | Laravel/DB | не экспортируется; у новых/обновлённых строк меняется |

Внешних ключей нет. `name` не имеет DB unique constraint; уникальность проверяет событие
`Article::saving()` отдельным запросом.

### Defaults модели

```php
[
    'parentId' => 0,
    'npp' => 0,
    'name' => '',
    'title' => '',
    'controller' => '',
    'body' => '',
    'directory' => false,
    'menuOn' => false,
    'isRoute' => false,
]
```

У `routeParams` model default отсутствует. При создании строки без поля остаётся DB `null`.

### Model validation, которая всё же срабатывает

Каждый `save()`/`update()` через модель проверяет:

- запись с `id = 1` обязана называться `root`, её `parentId` принудительно равен `0`;
- статья не может быть собственным прямым родителем;
- положительный `parentId` должен существовать;
- имя состоит из латинских букв, цифр, дефиса и подчёркивания;
- другая строка с тем же именем не найдена.

Модель не проверяет косвенные циклы, длину имени до DB write, `npp`, `directory`, полноту
`routeParams` и совместимость имени с PHP class name.

Bulk delete в `wipe()` не вызывает model events. У `Article` delete hooks сейчас нет, а архив
версий ведётся явно в API сохранения, поэтому импорт его не пополняет.

## Формат экспортируемого JSON

`ImportExportController` находит исходную статью по `id`, собирает все достижимые дочерние ID
по уровням и получает строки одним `whereIn` без явного `orderBy`.

Для каждой строки берутся все `$fillable` поля модели. Затем:

- `parentId` заменяется на `parentName`;
- для родителя внутри поддерева имя берётся из уже загруженных строк;
- только для верхней статьи при необходимости выполняется дополнительный запрос;
- у корня `parentName = null`;
- `id` и timestamps не входят в данные;
- JSON формируется с pretty print и Unicode без escaping.

Фактическая запись нормального экспорта:

```json
{
  "npp": 1,
  "name": "about",
  "title": "About",
  "controller": "",
  "body": "<h1>About</h1>",
  "directory": false,
  "menuOn": true,
  "isRoute": true,
  "routeParams": {
    "useController": false,
    "adminOnly": false,
    "utmParamsEnable": false,
    "getEnable": false,
    "postEnable": false,
    "bindKeys": false,
    "keysArr": []
  },
  "parentName": "root"
}
```

Имя download-файла строится из `article.name`: всё, кроме латинских букв, цифр, дефиса и
подчёркивания, удаляется; пустой результат заменяется на `articles`.

`collectIds()` не входит повторно в уже посещённый ID, поэтому повреждённое кольцо не зациклит
обход. Но export от `root` не увидит orphan/ring-компоненты, до которых root не достигает.

## Контракт импорта JSON

`ImportTree::FIELDS`:

```php
[
    'npp',
    'name',
    'title',
    'controller',
    'body',
    'directory',
    'menuOn',
    'isRoute',
    'routeParams',
]
```

`parentName` используется только для построения дерева. Любые другие ключи остаются в `$rows`,
но `fields()` их не передаёт модели.

### Нормализация

Текущий parser нормализует только `name`:

```text
name = trim((string) row.name)
```

`parentName` каждый раз приводится к string без trim. Остальные значения не проверяются до
`fill()`/DB. `npp` при создании явно приводится к integer.

### Отсутствующие значения

Для новой статьи `fields()` передаёт лишь реально существующие ключи. Остальное берётся из
model/DB defaults.

Для `root` полного импорта вызывается `update()` только с присутствующими полями. Поэтому
отсутствующие поля root не сбрасываются к defaults, а сохраняют старые значения. Из update
дополнительно исключены `name` и `npp`; `parentId` в FIELDS и так отсутствует.

## Объекты состояния `ImportTree`

| Property | Формат | Смысл |
| --- | --- | --- |
| `$rows` | `array<string,array>` | Строки файла по нормализованному имени |
| `$order` | `array<int,string>` | Имена: родители раньше детей |
| `$tops` | `array<int,string>` | Строки, чей parent отсутствует в файле |
| `$hook` | `array<string,int>` | Oak parent ID для каждой верхней строки |
| `$drop` | `array<int,int>` | ID существующих статей для удаления |
| `$report` | список объектов отчёта | Errors/collisions |
| `$ok` | boolean | Прошёл ли plan без ошибки |

Объект создаётся заново для каждого `check()` и `run()`. Между HTTP-запросами состояние не
сохраняется.

## API `API_Import`

### Envelope

`AbstractApi` всегда отвечает HTTP JSON envelope:

```json
{
  "status": true,
  "errorMsg": "",
  "data": {},
  "request": {}
}
```

При exception:

```json
{
  "status": false,
  "line": "/absolute/file.php 123",
  "errorMsg": "message",
  "data": {},
  "request": {}
}
```

`API_Import::handle()` после выполнения заменяет `request.text` на строку `...`, чтобы не
возвращать мегабайты файла и его код в response. Входной request и память сервера это не
уменьшает.

### Команды

| `command` | Дополнительные параметры | `data` при успехе |
| --- | --- | --- |
| `check` | `text: string`, `mode: partial|full` | `{ok, report}` |
| `run` | `text`, `mode` | `{ok, report}` |

`text` после trim должен быть непустым. `mode` принимается только точной строкой `partial` или
`full`.

Сервер не хранит факт первого `check` и не требует check token. Вызов `run` напрямую допустим:
он сам вызывает `plan()` и затем применяет результат.

### Import result

```json
{
  "ok": true,
  "report": [
    {
      "code": "replace",
      "params": {"name": "about"},
      "error": false,
      "done": true
    }
  ]
}
```

Ошибки содержимого файла не являются exception API: outer `status` остаётся `true`, а
`data.ok = false`.

## `plan()`

Порядок остановки:

1. `json_decode($text, true)` должен вернуть array.
2. `array_is_list()` должен быть true.
3. Список не должен быть пустым.
4. `readRow()` обрабатывает все строки.
5. При partial наличие ключа `root` запрещено.
6. `order()` строит порядок и выявляет ring.
7. Вызывается `planPartial()` или пустой `planFull()`.

После ошибки строки продолжают читаться внутри текущего foreach, поэтому report способен
содержать несколько row/name/duplicate errors. После foreach дальнейшие этапы не выполняются.
Ошибки верхнего уровня завершают plan сразу.

### `readRow()`

Проверяет:

- row является array;
- `name` не пуст после trim/string cast;
- имя соответствует допустимому набору символов;
- такое имя ещё не записано в `$rows`.

При ошибке row не добавляется. Все прочие поля parser не валидирует.

### `order()`

Первым проходом tops становятся записи, у которых:

```text
parentName is empty
OR
parentName is not a key in rows
```

Они сразу попадают в `$order`. Затем каждый проход по `$rows` добавляет статьи, чей parent уже
placed. Если проход никого не добавил, оставшаяся компонента считается ring, весь файл
отклоняется.

Алгоритм итеративный, но для глубокой цепочки повторно сканирует список и имеет квадратичный
worst case.

## План частичного импорта

`planPartial()` выполняет DB reads вне будущей transaction.

### 1. Коллизии

Для каждого имени файла:

1. ищется существующая статья по `name`;
2. root пропускается;
3. добавляется report `replace`;
4. `subtree(id)` собирает статью и всех потомков по уровням;
5. ID добавляются в объединённый `$drop`.

Один и тот же ID не обходится повторно внутри одного `subtree`, но пересекающиеся поддеревья для
разных collision names запрашиваются независимо.

### 2. Потерянные потомки

Все строки из `$drop` загружаются. Для статьи, имя которой отсутствует в JSON, добавляется:

```php
[
    'code' => 'drop',
    'params' => [
        'name' => $article->name,
        'parent' => $parentNameInsideDrop,
    ],
    'error' => false,
    'done' => false,
]
```

### 3. Hooks верхних статей

Для каждого top:

- пустой `parentName` означает root;
- иначе существующий oak parent ищется по имени;
- если parent найден и его ID не входит в gone, он записывается в `$hook`;
- отсутствующий или удаляемый parent заменяется на root;
- при непустом исходном имени добавляется `attach_root`.

## Выполнение partial

`applyPartial()`:

1. `wipe($drop)` получает имена и одним bulk delete удаляет строки;
2. проходит `$order`;
3. для внутреннего ребёнка находит уже созданного parent по имени и берёт `npp` из JSON;
4. для top берёт hook/root и вычисляет `max(npp) + 1`;
5. `create()` создаёт новую `Article` через model save.

Соседи не перенумеровываются, `directory` родителей не обновляется, а значения imported
`directory` не сверяются со структурой.

## Полный импорт

`planFull()` не читает существующие статьи и не добавляет report lines.

`applyFull()`:

1. получает все ID кроме root;
2. `wipe()` удаляет их bulk query;
3. проходит `$order`;
4. строка `root` обновляет существующую `Article::find(1)` без `name` и `npp`;
5. прочие строки создаются заново;
6. parent из файла находится по уже созданному name, внешний parent заменяется root;
7. `npp` берётся из JSON или становится `0`.

Если root отсутствует, существующая строка root не обновляется. Полный импорт не сбрасывает
auto-increment и не пытается сохранить старые IDs.

## Транзакционная граница `run()`

Фактический порядок:

```text
plan outside transaction
DB::transaction(applyFull or applyPartial)
after commit: generate(gone)
mark report lines done
return ok
```

Если DB transaction бросает exception:

```text
rollback database
generate([]) from rolled-back database
rethrow exception
```

Вызов `generate([])` внутри catch может сам бросить exception и заменить исходную причину.

После commit `generate()` **не останавливается на первой беде**: каждая статья
пишется в своём `try`, а те, кому файлы не достались, возвращаются списком имён.
Они уходят в отчёт строками `file_failed`, а ответ остаётся успешным — база уже
приняла дерево, и повторять импорт из-за файлов не надо и вредно. Чинить надо
названные статьи: поправить и сохранить.

Раньше исключение с одной статьи обрывало проход, и сайт оставался в смеси —
база новая, часть файлов новая, часть старая, часть удалена, — а наружу уходила
ошибка, которая подталкивала повторить уже применённый импорт.

## Генерация рабочих файлов

`generate()` сначала вызывает `deleteMpro(['name' => ...])` для каждого исчезнувшего имени,
затем проходит все текущие статьи по `id` и вызывает `createMpro()`.

Для каждой статьи `createMpro()`:

1. снова удаляет view/controller этой статьи;
2. всегда пишет `body` в `{MAGIC_VIEW_DIR}/{name}.blade.php`;
3. вычисляет controller text;
4. если `routeParams.useController` truthy, пишет PHP-файл;
5. запускает `php -l` через `shell_exec`;
6. бросает exception при lint failure.

Удаление файлов использует подавленный `unlink` и не проверяет результат. `MagicFile::saveToFile`
проверяет запись и инвалидирует opcache, но пишет файл напрямую, не через temporary + atomic
rename.

Генерация всего дерева — последовательный loop, не файловая transaction. Ошибка оставляет уже
обработанные файлы новыми, ещё не обработанные старыми, а часть gone-файлов удалёнными. Invalid
controller записывается до lint и остаётся на диске после ошибки.

## Frontend import state machine

Основные refs:

```text
fileName  displayed selected name
text      entire FileReader result
mode      partial by default
report    server report
ready     last check returned data.ok
apiActive blocks concurrent button calls
```

### Выбор файла

`pickFile()` сразу очищает report, ready и text, затем асинхронно читает выбранный файл через
`FileReader.readAsText()`. `accept=.json` является только browser hint; server extension не
проверяет.

### Check

Отправляет `{command: check, mode, text}`. `ready = res.ok`, поэтому collision report не блокирует
run, а validation error блокирует.

### Run

После confirmation отправляет текущие `{command: run, mode, text}`. После любого
нормального ответа ready сбрасывается, при success или validation response обновляется report.

Watcher на `mode` сбрасывает `ready` и report: проверка относится к паре «файл + режим», и
после смены режима «Импорт» закрыт до новой проверки.

## Коды отчёта

Каждая строка:

```php
[
    'code' => string,
    'params' => array,
    'error' => bool,
    'done' => bool,
]
```

| Code | Params | Когда |
| --- | --- | --- |
| `replace` | `name` | В site уже есть импортируемое имя |
| `drop` | `name`, `parent` | Старый descendant исчезнет и не вернётся |
| `attach_root` | `name`, `parent` | Внешний parent отсутствует/удаляется |
| `e_json` | — | `json_decode` не вернул array |
| `e_not_list` | — | Верхний array не является list |
| `e_empty` | — | Список пуст |
| `e_row` | `line` | Элемент не array |
| `e_name` | `line` | Пустое имя |
| `e_name_bad` | `name`, `line` | Недопустимые символы имени |
| `e_dup` | `name` | Повтор имени |
| `e_root_partial` | — | Root в partial-файле |
| `e_ring` | — | Неразрешимая parent chain |

Текст строится только в Vue: `t('imp_' + code, params)`. Backend language-independent.

При успешном run все report lines получают `done=true`. При exception report не возвращается из `ImportTree::run()`.

## Взаимодействие с другими подсистемами

### Архив версий

`ArticleArchive::add()` вызывается из обычного API сохранения статьи. ImportTree его не вызывает.
Bulk delete также не удаляет versions: внешнего ключа нет. После замены статей прежние версии
остаются привязаны к прежним numeric IDs и считаются версиями удалённых статей.

### Cron

Cron task хранит `article|method`, то есть имя, а не ID. Full import не удаляет cron rows. После
импорта checker считает задачу рабочей только при наличии статьи, controller class и собственного
public method.

## Сложность и конкуренция

- Весь JSON одновременно находится в browser string, request body, PHP string и decoded array.
- `order()` имеет worst case O(N²) на глубокой цепочке.
- Partial plan делает lookup по имени для каждой импортируемой строки.
- `subtree()` делает запрос на каждый уровень каждого collision subtree.
- Apply снова ищет родителей и `max(npp)` запросами.
- Generate проходит всё итоговое дерево и пишет файлы последовательно.

PlanPartial выполняется до DB transaction и без locks. Между check и run нет revision/hash, а
между plan внутри run и transaction есть окно. Параллельная правка может изменить collision set,
hook или max position.

## Границы доверия

Import endpoint принимает данные только после `magic.auth`, но CSRF middleware для него явно
отключён. Внутри доверенного admin request всё равно следует считать опасными:

- `controller` — исполняемый PHP source;
- `body` — Blade source;
- `name` — часть route, view filename, controller filename/class;
- `routeParams` — управление route/controller behavior.

При усилении защиты нельзя ограничиться frontend: API вызывается напрямую, а run не требует
предварительного check request.

## Как безопасно менять модуль

### Добавить поле статьи

Проверить единым change:

1. новая migration и DB default;
2. `Article::$fillable`, cast, attribute/mutator;
3. editor и обычный API;
4. `ImportTree::FIELDS`;
5. exporter, который берёт fillable;
6. full import с файлом старой версии;
7. generated resources, если поле влияет на них;
8. docs и tests.

### Изменить parent/name правила

Синхронно проверить:

- `ImportTree::readRow()` и `order()`;
- `Article::saving()`;
- PHP classname constraints;
- DynamicRouteHandler и `___` mapping;
- `MagicProBuilder` filenames/classes;
- exporter `parentName`;
- Cleanup name/ring repair;
- MCP article tools;
- cron `article|method` links.

### Изменить файловую генерацию

Нужно проектировать DB и filesystem как две разные системы. DB transaction не откатывает файлы.
Безопасная схема требует staging, полного lint/compile до публикации, atomic rename и recovery
после сбоя.

## Минимальная тестовая матрица

Автоматических тестов import в пакете не найдено. Нужны тесты без production DB и без
реального admin browser.

### Parser и plan

- invalid JSON, object вместо list, empty list;
- scalar row, missing/blank/bad/duplicate name;
- multiple tops, missing parent, self-ring и multi-row ring;
- root partial;
- invalid types, lengths, routeParams, npp и directory;
- PHP-compatible и incompatible controller names;
- extra fields игнорируются осознанно.

### Partial

- import без collisions;
- collision с глубоким subtree;
- overlapping collision subtrees;
- hook к существующему parent;
- hook parent входит в gone;
- top append position;
- root остаётся неизменным;
- directory/npp после результата;
- concurrent mutation between plan/apply.

### Full

- файл с root и без root;
- root fields present/missing;
- source IDs/timestamps ignored;
- external parents go to root;
- archive/cron side effects;
- DB exception rolls back;
- generation exception after commit is observable/recoverable.

### Frontend

- file selection resets readiness;
- mode change resets readiness;
- stale FileReader callback;
- report rendering and i18n codes;
- normalized overwrite confirmation;
- double actions blocked;
- Import and MCP embedding.

### Security

- non-admin API access;
- CSRF policy;
- untrusted controller/Blade threat model;
- run requires admin;
- response never echoes input `text`.

## Перед выпуском изменения

1. Сверить внешний JSON/API контракт со старыми exports.
2. Проверить partial и full отдельно.
3. Инъецировать сбой на каждом DB и filesystem этапе.
4. Проверить восстановление не только строк, но и рабочих файлов.
5. Проверить tree invariants через независимый read-only анализ результата.
6. Проверить страницу Import.
7. Обновить `use.md` и этот документ.
8. Не использовать production content, controllers или credentials в fixtures.
