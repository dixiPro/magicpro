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

- **Backend**: Laravel 12, Eloquent, MoonShine 2.x, Breeze.
- **Frontend**: Vue 3, Bootstrap 5, PrimeVue.
- **DevOps**: Ubuntu, Nginx, SQLite.

#### 2025-19-10

##### Added / Changed

- Moved all sources to `packages/dixi/magicpro` to structure it as a package
- Introduced dynamic route handler (`DynamicRouteHandler.php`)
- Added installation command (`InstallMagicProCommand.php`)
- Consolidated paths in `MagicGlobals.php`
- Switched from Monaco to ACE editor
- Implemented Blade and PHP formatters (Prettier)
- Removed MoonShine admin panel from the package

#### 2025-10-10

##### Added

- File manager
- Transliteration of article names
- LiveWire controllers and Blade integration

#### 2025-05-10

##### Added

- Testing liveWire
- MoonShine admin panel
- Breeze authentication scaffolding
- Blade syntax highlighting for Monaco Editor
- Monaco Editor integration
- Route, controller, and view generation from Article model
- Core project foundation

## Installation

```bash
# development directory where MagicPro will be placed
mkdir -p packages/dixi/magicpro

# clone the repository
git clone https://github.com/dixiRu/magicpro

# add to composer
composer config repositories.magicpro path packages/dixi/magicpro

# enable symlink option
jq '.repositories.magicpro.options.symlink=true' composer.json > composer.tmp && mv composer.tmp composer.json

# install dependencies
composer require magicpro/magicpro:"^0.1.0"

# run Laravel migrations — creates 'article' and 'userAdmin' tables
php artisan migrate

# create necessary folders
php artisan magicpro:install
```

**Vite build**
Vite is configured to build outside the project root.

**Recommended Laravel**

```bash
sudo chown -R $USER:www-data .
sudo find . -type f -exec chmod 664 {} \;
sudo find . -type d -exec chmod 775 {} \;
```

MIT © dixiRu
