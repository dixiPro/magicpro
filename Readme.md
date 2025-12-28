# MagicPro

![Laravel](https://img.shields.io/badge/Laravel-12-red?logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-42b883?logo=vue.js&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

**MagicPro — the speed of a CMS + the flexibility of Laravel 12 in one solution. Ultra-fast website development and modification.**

## 🚀 Features

- Manage controllers, views, routes, pages, menus, structure through a single model.
- Support parameters for flexible behavior customization.

## 🔑 Key Functions

- **Articles**: hierarchical structure, content storage, routing, and menus.
- **MagicProBuilder**: code generation (controllers, views, routes).
- **Admin Editor**: manage articles, routes, and settings.
- **Frontend**: integration with Vue 3, Bootstrap 5, PrimeVue.
- **Editor**: integration with ACE Editor.

## 🛠 Technologies

- **Backend**: Laravel 12
- **Frontend**: Vue 3, Bootstrap 5, PrimeVue.
- **DevOps**: Ubuntu, Nginx, SQLite, MySql, Postgres

### ⚙️ Installation

```bash
# in project root
composer require dixipro/magicpro
php artisan magicpro:install
sudo chown -R :www-data dataMagicPro
php artisan migrate
```

### Added / Change

#### 2025-12-25

The first website on MagicPRO-laravel has been launched.

Multilingual version has been implemented

Installation bugs have been fixed

Livewire was fixed.

#### 2025-12-05

The MagicPro-based site has been built; we are currently testing.

The site can now run in static mode. Performance increased significantly. A crawler was added that visits pages and generates static HTML files. As a result, Nginx serves an HTML file if it exists, otherwise routing takes over.

A file manager was added, including editing of JS and CSS files with formatters.

A Setup section was added to the admin panel. All constants are being moved into a single file (work in progress).

Filament has been added to the Magalif site.

Magalif data was exported in JSON format, and inside MagicPro a grabber was implemented that downloaded all this data into Filament.

MagicPro and Filament work together very well.

#### 2025-11-12

- add search in admin
- add formatter status

#### 2025-11-06

- change package structure
- register packagist.org
- composer installer
- fixed bugs

#### 2025-10-27

- Dynamic Routing
- Setup Dynamic Routing: binding parameters
- 404 error handling
- Admin testing page: attr for writing atrr
- import from MagicPro Xml

#### 2025-10-23

- Export-import JSON
- Moved all sources to `packages/dixi/magicpro` to structure it as a package
- Introduced dynamic route handler (`DynamicRouteHandler.php`)
- Added installation command (`InstallMagicProCommand.php`)
- Consolidated paths in `MagicGlobals.php`
- Switched from Monaco to ACE editor
- Implemented Blade and PHP formatters (Prettier)
- Removed MoonShine admin panel from the package

#### 2025-10-10

- File manager
- Transliteration of article names
- LiveWire controllers and Blade integration

#### 2025-10-05

- Testing liveWire
- MoonShine admin panel
- Breeze authentication scaffolding
- Blade syntax highlighting for Monaco Editor
- Monaco Editor integration
- Route, controller, and view generation from Article model
- Core project foundation

## Note

MIT © dixiRu
