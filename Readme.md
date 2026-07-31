# MagicPro

![Laravel](https://img.shields.io/badge/Laravel-13-red?logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-42b883?logo=vue.js&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

**MagicPro combines the speed of a CMS with the flexibility of Laravel 13. It provides tools for rapid website development, administration, authentication, email delivery, and content management.**

## 🧭 Why This Exists

Modern applications are largely built around APIs. Administrative operations are handled through an admin API, while public pages communicate with a client API.

In many projects, controllers contain very little business logic. They prepare API requests, receive data, and pass it to Blade templates.

Over time, this creates many small controllers, views, routes, and configuration files. Finding the correct file and constantly switching between directories adds unnecessary overhead.

MagicPro simplifies this workflow.

Controllers, Blade templates, routes, pages, menus, and application settings can be managed through a unified database structure and administration interface.

Instead of constantly moving between files and folders, pages, API calls, templates, users, and system settings can be managed from one place.

## 🚀 Features

- Manage controllers, views, routes, pages, menus, and website structure through a unified model.
- Built-in administration panel and API.
- User registration, authentication, authorization, and role management.
- Scheduled and immediate email delivery.
- SMTP support for any compatible email provider.
- Amazon SES API integration.
- Reusable helpers for common application operations.
- Flexible parameters for customizing system behavior.

## 🔑 Key Components

- **Articles**: hierarchical content structure, routing, pages, and menus.
- **MagicProBuilder**: generation of controllers, views, routes, and application code.
- **Admin Panel**: management of content, routes, users, settings, and email queues.
- **Authentication API**: registration, login, logout, authorization, and user management.
- **Mail System**: immediate and scheduled email delivery through SMTP or Amazon SES API.
- **Helpers**: email delivery, Telegram notifications, and other reusable application utilities.
- **Frontend**: integration with Vue 3, Bootstrap 5, and PrimeVue.
- **Code Editor**: integration with ACE Editor.
- **Static Generator**: generation of static website pages.

## 🛠 Technologies

- **Backend**: Laravel 12,13
- **Frontend**: Vue 3, Bootstrap 5, PrimeVue
- **Email**: SMTP, Amazon SES
- **Databases**: SQLite, MySQL, PostgreSQL
- **Editor**: ACE Editor
- **DevOps**: Ubuntu, Nginx

## ⚙️ Installation

Install Laravel — see [Cookbook.md](Cookbook.md#laravel).

```bash
# Run in the project root
composer require dixipro/magicpro

php artisan migrate

(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd $(pwd) && /usr/bin/php artisan schedule:run >> /dev/null 2>&1") | sort -u | sudo crontab -u www-data -
```

_Last command adds Laravel Scheduler to the www-data crontab. The current path is inserted automatically._

For dev see [Cookbook.md](Cookbook.md#magicpro).

## Added / Change

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
