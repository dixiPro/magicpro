# MagicPro CHANGELOG

### 2026-08-17

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
