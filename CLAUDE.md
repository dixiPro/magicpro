# CLAUDE.md — MagicPro package

Product, architecture and domain rules. Workflow and methodology rules live in the root
`CLAUDE.md` of the host application.

## MagicPro

**MagicPro** is a CMS that combines the speed of a CMS with the flexibility of Laravel 13.
Controllers, Blade views, routes, pages, menus and settings are managed through a single
database structure (Articles) and an admin panel instead of scattered files.

Key components:

- **Articles** — hierarchical content structure: routing, pages, menus.
- **MagicProBuilder** — generation of controllers, views, routes and application code.
- **Admin panel + API** — content, routes, users, settings, email queues.
- **Auth API** — registration, login, logout, authorization, roles.
- **Mail system** — immediate and scheduled delivery via SMTP or Amazon SES API
  (`src/Mail/API_Mail.php`).
- **Helpers** — mail, Telegram, crypt and other reusable utilities
  (`src/Helpers/MproHelper.php`).
- **Frontend** — Vue 3, Bootstrap 5, PrimeVue, ACE editor.
- **Static generator** — generation of static site pages.

Databases: SQLite (dev), MySQL, PostgreSQL. See `Readme.md` for the product overview and
`Cookbook.md` for setup recipes.

## Architecture

MagicPro is a CMS based on database-backed Articles.

Main request flow:

1. `admin/web.php` registers admin routes and the catch-all route.
2. `MagicProSrc\Routing\DynamicRouteHandler` resolves the first URL segment to an `Article`.
3. Generated controllers and views are stored under `storage/dataMagicPro/`.
4. `MagicProBuilder::createMpro()` regenerates these files from Article data.

Do not manually edit generated files under `storage/dataMagicPro/`.

The database is a sqlite file at `database/database.sqlite`. Articles are edited through
`API_ArticlesPostController` (`POST /a_dmin/api/articles`, behind `magic.auth`). Saving an
article is not a plain row write: it regenerates files via `createMpro`/`deleteMpro` and
recomputes `npp`/`directory` in transactions, so a direct SQL UPDATE desyncs the DB from the
generated files.

The `MagicProSrc\Api` layer (`AbstractApi::run` + e.g. `API_Auth`) dispatches a command to a
method via `$map`, passes params as a plain array, throws errors as exceptions / `ApiException`
(carries extra data such as `situation`), and returns `status / errorMsg / data / request`.

The mail subsystem mirrors that layer with its own `AbstractMailApi` / `API_Mail`
(commands: `sendNow`, `sendLater`, `sendQueue`, `emailQueue`, `messagesList`, `addressesList`,
`deleteEmail`, `deleteQueueByEmail`).

## Important paths

- Dynamic router: `src/Routing/DynamicRouteHandler.php`
- Global config: `src/Config/MagicGlobals.php`
- Config schema: `src/Config/magicSchema.php`
- Admin controllers: `admin/controller/`
- Admin views: `admin/views/`
- Package frontend: `admin/js/app/`
- Package Vite config: `vite.config.js`

## Domain rules

- Article names must match `^[A-Za-z0-9_-]+$`.
- Add routes that must bypass the dynamic router to `EXCLUDED_ROUTES`.
- Do not modify the `.` ↔ `___` route-name conversion without checking both directions.

## Frontend commands

```bash
cd packages/dixipro/magicpro
npm install
npm run dev
npm run build
```
