# MagicPro CHANGELOG

### 2026-08-26

- `MproHelper::sendMail()` принимает `replyTo` и `fromName` — `API_Mail` это умел
  с самого начала, короткий вход со страницы не пробрасывал. Заявка с формы
  уходит с `MAIL_FROM_ADDRESS`, который никто не читает, и «Ответить» у менеджера
  должно вести клиенту; `fromName` — только подпись перед адресом, сам адрес
  подтверждён в SES и не меняется. Ключа нет или он пуст — поведение прежнее.
  Разбор всех ключей письма — в `docs/ru/helpers/use.md`.

### 2026-08-25 — AWS

- Настройка почты Amazon тремя командами вместо прогулки по консоли AWS, которую
  никто не помнит наизусть. `magicpro:aws-setup` — «сайт может слать письма»:
  IAM-пользователь, права, ключ и посчитанный из него SMTP-пароль.
  `magicpro:aws-webhook` — «сайт узнаёт, что с письмами стало»: топик,
  разрешение для SES публиковать в него, подписка и набор конфигурации с
  событиями; та же команда и заводит всё с нуля, и меняет адрес.
  `magicpro:aws-status` показывает, как всё настроено сейчас, ничего не трогая.
- Граница между первыми двумя проведена по смыслу: топик и набор конфигурации
  существуют ради вебхука, и сайту, которому события не нужны, хватает одной
  первой команды. У такого сайта `AWS_SES_CONFIGURATION_SET` пуст, SES событий
  не шлёт — правильное поведение, а не недонастройка.
- Параметры — только из ini-файла, `aws-setup.ini`, один на сайт для всех трёх
  команд; флаги несут пути и ничего больше. Секретов в файле нет, поэтому он
  лежит открыто и через полгода отвечает на вопрос, как всё было настроено. Имена
  ресурсов там не хранятся: они считаются из `user`, одинаково всеми командами,
  так что две команды не могут иметь в виду разные топики.
- Ключ настройки спрашивается в терминале при каждом запуске и нигде не
  сохраняется. Проверка на терминал строже обычной: `isInteractive()` при
  запуске через пайп остаётся истинным, и вопрос молча прочёл бы пустую строку.
- Секреты проекта пишутся только в `aws-{домен}-{дата}.result`, права `600`,
  `*.result` дописывается в `.gitignore` — команда говорит, что дописала. Файл
  пишется и после падения на середине: секрет ключа AWS отдаёт один раз, и
  терять его вместе с сообщением об ошибке нельзя. Рядом с ключом там же лежит
  `AWS_DEFAULT_REGION` — ключ работает только в своём регионе и ехать в проект
  должен вместе с ним, — `MAIL_FROM_NAME` и комментарием адрес вебхука.
- `magicpro:aws-webhook` стучится в адрес постом до того, как что-либо создано, и
  ждёт ответа самого обработчика, а не просто живого хоста. SNS подтверждает
  подписку таким же стуком: не ответили — подписка висит `PENDING` трое суток,
  а команда рапортует об успехе всех прочих шагов. Ловится и перехват `/awsHook`
  динамическим роутером, и забытый в ini чужой адрес.
- В политику топика добавляется разрешение `ses.amazonaws.com` публиковать —
  без него события не приходят, а выглядит это как молчащий вебхук.
- Дока — `docs/ru/aws/`.

### 2026-08-25

- A cron task points at a public method of a controller, `dataCache|task`, and
  the scheduler calls that method straight: no `Request`, no `Env`, no view.
  `MagicController` got a public `run()` for the same reason — a controller can
  now be called as a service, not only as a page. The method is an entry point
  of its own; `process()` may hand it the same work when the job has to be
  reachable by URL as well. One controller holds as many such methods as there
  are tasks. Parameters go as one array, the json of the task, and the method
  takes one array.
- Because `handle()` is out of the way, nothing swallows an exception any more.
  It used to be caught inside the controller and turned into a 500 page that
  cron dropped, text and all; now it reaches `cron.log` with its own words, and
  the "run now" button shows it on the screen. That button is the check: what
  `save` asks for is only that the article and its controller class exist. A
  route, `adminOnly`, `postEnable` are about a page, and a method is not a page
  — an article that is only a place to keep a controller carries a task just as
  well.
- The method name has to start with a letter and hold letters, digits and
  underscore. `__construct` and the rest of the php magic are refused by that
  one rule, without a list of bans.

### 2026-08-24

- Cron tasks are created in the admin panel, `/a_dmin/cron`: a name, an article,
  json parameters and a cron string. The scheduler then calls the controller of
  that article — an ordinary MagicPro controller, the same one a browser opens,
  so a task is debugged like any page. Nothing is written to php files, and a
  change in the admin panel works from the next minute: the list is read again on
  every pass of the scheduler. The article has to be a route with a controller,
  `postEnable` and `adminOnly`; `CronTaskChecker` holds that list and marks a task
  red once its article is renamed or gone. Parameters always travel as post.
  Registration is guarded twice — a missing table leaves the schedule without
  dynamic tasks instead of breaking artisan, and a broken cron string is refused
  before it can take down the pass together with `magicpro:heartbeat` and
  `magicpro:sendQueue`.
- Cron does not judge the result of a task, and there is no last status in the
  table. Retries do not exist, a task never switches itself off, so there is
  nothing to do with such a verdict: what happened inside is the controller's own
  business. `last_run_at` says only that the scheduler reached the task.
- The log of the tasks is `storage/logs/cron.log`. Troubles are always written; a
  line about a task that ran — with the time it took — while `CRON_LOG_SUCCESS` is
  on in Setup.
- The diagnostics of the admin home page tells about the checks that passed, not
  only about the troubles. Silence is not a report: an empty screen used to be
  the only sign that everything is in place. The list is translated, unlike the
  troubles, which stay in English because they also go to the log.

### 2026-08-17

- Mail: the duplicate check no longer closes an address and subject for good.
  `findDduplicates()` compared the status of the last letter to `sent` alone,
  and `sent` lives for seconds — the webhook turns it into `delivered` and then
  `open`, and exhausted attempts turn it into `failed`. Every one of those took
  the first branch and answered `duplicate email`, so the pair became
  single-use and the minute threshold was never reached. Now a letter still in
  the queue (`queued`, `retrying`) answers `duplicate email`, and anything that
  already went out is only held back by `retryTimeEmail`, 60 seconds by
  default. A blocked address is still refused earlier, by `checkEmail()`.
  Found from the shop, where an order mail carries one subject for everyone: a
  returning customer would have been mailed once in a lifetime.
- webp out of an avif source. `cwebp` reads png, jpeg, tiff and webp and nothing
  else, so a record made by the feed cropper — avif, that being the default
  `RESIZE_FORMAT` — got no webp at all. Such a source now goes to vips, the same
  way `iwebp` does; the format, the extension and the cache stay as they were.
- `x-magic::img` drops a format that failed instead of printing a `<source>`
  with an empty address, and prints no `<picture>` when nothing was built.

### 2026-08-16

- MCP reaches the feeds. `feed-api` reads — `feedsList`, `feedGet`, `itemsList`
  (250 records a page at most), `itemGet`. `feed-api-write` writes —
  `itemCreate`, `itemSave`, `itemDelete`. Both run one command over
  `AbstractFeedApi::run()`, with their own list of what is allowed. Writing is a
  separate tool on purpose: the permission is the connected tool, not a flag.
  `itemDelete` deletes one record per call and only on the second call, the first
  one shows what would go. Image fields are refused: a file gets into a feed
  through the cropper of the admin panel.
- Images: the `url` key is gone, feeds and the resizer both speak `path` — the
  address from `public`, empty when the resize failed.
- Setup got a button that clears the whole resize cache
  (`ImageJob::clearAll()`).
- A picture component, `<x-magic::img :img="#img1#" width="700" mobile="2" />`.
  It takes the place on the page, not the files: three sources (avif, webp, jpg)
  in a plain and a double size, `sizes` counted from `width` and `mobile`, and
  the widths in `srcset` read back from the resizer, since `MAX_RESIZE` may trim
  what was asked for. The `src` holds a large copy for image search — Google
  indexes `src` and reads avif there, while a live browser never fetches it.
- `<x-magic::img_box>` wraps that picture in an inline-block `span` that holds
  the place: for the text of a record, where no column sets the width. It is a
  `span` and not a `div` because the editor puts the tag inside a `<p>`, and the
  parser pushes a block element out of it. The desktop width rides inline, the
  phone share comes from the `mimg-m2`…`mimg-m4` classes of `design/style.css`.
- A pagination component, `<x-magic::paginator :items="$items" />`, with its own
  markup: the wording of the Laravel view sits inside the framework and only
  translations can change it.
- `MAX_RESIZE` may now go up to 10000.

- Installation rewritten. The whole check runs in `MagicProSrc\Install\Installer`,
  the admin controller only calls it. The mark is written after a full success,
  so a half-finished install no longer reports itself as done. The first admin is
  created by `php artisan magicpro:admin`, not by a migration.
- Feeds: a record has a `__slug`, unique inside its feed. It is either counted
  from a string field of the schema (`slugFrom`) or typed by hand.
  `MproHelper::translitForUrl()` translates Russian and Serbian.
- Feeds: order of fields in the record form, set by dragging on the feed screen
  (`orderForm`), next to the order of columns in the list.
- Feeds: text of a record understands `#field#` substitutions and tags of magic
  components — `MproHelper::feedText($item, 'body')`. Only the tag itself goes
  through Blade, so text written by an operator is never compiled.
- Feeds: image fields keep `path` next to `url` — the same address without the
  host, the one the resizer understands.
- The visual editor moved from Quill to TipTap, in its own component
  `htmlEditor.vue`: own toolbar, paste from Word cleaned by the schema, hotkeys
  working in any keyboard layout. Ace tab got a format button.
- Feed admin: the code of a field is locked only when that column already holds
  data, the structure screen shows the schema json, and the Structure/Data tabs
  stay inside the feed you opened.

### 2026-08-05

Feeds: Development Begins

The MCP server can now build pages. I’m as excited as a little kid.

### 2026-07-31

Start of development of MSP server

### 2026-07-25

Added an email service with immediate and scheduled email delivery.

### 2026-06-18

Added registration and authentication APIs.

### 2026-05-17

Completely redesigned the article tree. h-tree was replaced with PrimeVue.
Bug fixes and UI polish

### 2026-04-13

- Added dark theme.
- Hotkeys for editing.
- Improved installation: folder creation moved from model to admin panel; automatic generation of views and controllers during installation.

### 2026-04-05

- Launched second website on MagicPRO (Laravel)
- Launched new store powered by MagicShop (headless): catalog admin + frontend/backend via MagicPRO
- Fixed bugs

### 2025-12-25

The first website on MagicPRO-laravel has been launched.

Multilingual version has been implemented

Installation bugs have been fixed

Livewire was fixed.

### 2025-12-05

The MagicPro-based site has been built; we are currently testing.

The site can now run in static mode. Performance increased significantly. A crawler was added that visits pages and generates static HTML files. As a result, Nginx serves an HTML file if it exists, otherwise routing takes over.

A file manager was added, including editing of JS and CSS files with formatters.

A Setup section was added to the admin panel. All constants are being moved into a single file (work in progress).

Filament has been added to the Magalif site.

Magalif data was exported in JSON format, and inside MagicPro a grabber was implemented that downloaded all this data into Filament.

MagicPro and Filament work together very well.

### 2025-11-12

- add search in admin
- add formatter status

### 2025-11-06

- change package structure
- register packagist.org
- composer installer
- fixed bugs

### 2025-10-27

- Dynamic Routing
- Setup Dynamic Routing: binding parameters
- 404 error handling
- Admin testing page: attr for writing atrr
- import from MagicPro Xml

### 2025-10-23

- Export-import JSON
- Moved all sources to `packages/dixi/magicpro` to structure it as a package
- Introduced dynamic route handler (`DynamicRouteHandler.php`)
- Added installation command (`InstallMagicProCommand.php`)
- Consolidated paths in `MagicGlobals.php`
- Switched from Monaco to ACE editor
- Implemented Blade and PHP formatters (Prettier)
- Removed MoonShine admin panel from the package

### 2025-10-10

- File manager
- Transliteration of article names
- LiveWire controllers and Blade integration

### 2025-10-05

- Testing liveWire
- MoonShine admin panel
- Breeze authentication scaffolding
- Blade syntax highlighting for Monaco Editor
- Monaco Editor integration
- Route, controller, and view generation from Article model
- Core project foundation

## Note

MIT © dixiRu
