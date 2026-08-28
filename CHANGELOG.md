# MagicPro CHANGELOG

### 2026-08-28

- Added cleanup of orphaned image-cache files whose source image no longer exists.
- Image cache controls in `/a_dmin/setup` are now split into orphan cleanup and full cache clear.
- Feed image names are length-limited and validated instead of being silently transliterated.
- Feed images now require `alt` text and store it with the upload itself.
- The crop button of the feed image dialog is always available.
- Feed string fields are edited in a growing textarea with a counter near the 255-character limit.
- Admin diagnostics print the AWS hook address and knock at it with a POST.
- New admin page `/a_dmin/documentation`: helpers of `MproHelper` with their doc, read from the phpdoc of the source.
- Every public method of `MproHelper` carries a bilingual `@ru`/`@en` phpdoc.
- A feed can set the order its record list opens in — a string field or a date, ascending or descending.
- The eye in the header of the record list filters by visibility: all records, hidden only, visible only.
- A feed can name the article that shows its records; the record form then links to `/<article>/<slug>`.
- `translitString` now returns lowercase Latin text with normalized dashes.

### 2026-08-26

- `MproHelper::sendMail()` now supports `replyTo` and `fromName`.

### 2026-08-25 — AWS

- AWS SES setup is now handled by `magicpro:aws-setup`, `magicpro:aws-webhook` and `magicpro:aws-status`.
- Mail sending and event webhooks are configured independently.
- AWS settings are stored in one per-site `aws-setup.ini`, without secrets.
- Setup credentials are entered interactively and are never stored.
- Generated AWS credentials are saved only in protected `*.result` files excluded from Git.
- Webhook setup validates the endpoint and SNS subscription before use.
- SNS topics now receive the policy required for SES event delivery.
- Documentation added under `docs/ru/aws/`.

### 2026-08-25

- Cron tasks can call public controller methods directly, and `MagicController::run()` allows controllers to be used as services.
- Cron exceptions are no longer swallowed and are visible in `cron.log` and manual runs.
- Cron method names are validated to prevent calls to PHP magic methods.

### 2026-08-24

- Cron tasks are managed from `/a_dmin/cron` and reloaded by the scheduler without editing PHP files.
- Invalid tasks or cron expressions no longer break the rest of the scheduler.
- `last_run_at` records only that the scheduler reached the task.
- Cron logging now includes errors and optional successful-run timing.
- Admin diagnostics now also show successful checks.

### 2026-08-17

- Mail duplicate protection now blocks only queued/retrying messages; sent mail uses `retryTimeEmail`.
- AVIF-to-WEBP conversion now uses `vips`.
- `<x-magic::img>` no longer renders broken sources or empty `<picture>` elements.

### 2026-08-16

- MCP feeds now have separate read and write tools.
- Added responsive image and paginator Blade components.
- Image data now consistently uses `path` instead of `url`.
- Installation was moved to `MagicProSrc\Install\Installer`, with admin creation handled by `magicpro:admin`.
- Feeds gained unique slugs, draggable field ordering and `#field#`/Magic component rendering.
- The visual editor was replaced with TipTap.

### 2026-08-05

- Feed development started, including MCP support for building pages.

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
