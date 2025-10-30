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
- **DevOps**: Ubuntu, Nginx, SQLite.

## ⚙️ Installation for use

composer require dixiRu/magicpro:dev-main

## ⚙️ Installation for dev

```bash
#install Laravel
#see note


#install livewire
composer require livewire/livewire


# from Laravel project root — create development directory for MagicPro
mkdir -p packages/dixi
cd packages/dixi

# clone the repository
git clone https://github.com/dixiRu/magicpro

# с ключом
git clone git@github.com:dixiRu/magicpro.git


cd ../../ root project

# add to composer
composer config repositories.magicpro path packages/dixi/magicpro

# enable symlink option
jq '.repositories.magicpro.options.symlink=true' composer.json > composer.tmp && mv composer.tmp composer.json
# if jq not in server
sudo apt install jq -y


# install dependencies
composer require magicpro/magicpro:"^0.1.0"
composer require packages/dixi/magicpro:"^0.1.0"

# run Laravel migrations — creates 'article' and 'userAdmin' tables
php artisan migrate

# create necessary folders
php artisan magicpro:install
cd ..
# шinstall fro development
npm i
```

**Vite build**

Vite is configured to build outside the project root.

```bash
cd packages/dixi/magicpro
npm i
npm run dev
npm run bhuild
```

#### 2025-27-10

##### Added / Change

- Dynamic Routing
- Setup Dynamic Routing: binding parametrs
- 404 error handling
- Admin testing page: attr for writing atrr
- import foem MagicPro Xml

#### 2025-23-10

##### Added / Change

- Export-import JSON

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

## Note

### Laravel installation

````bash
# Install Composer (if not installed)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Check version
composer -V

# Create a new Laravel project
composer create-project laravel/laravel myapp

composer create-project laravel/laravel:^12 myapp



# Go to the project folder
cd myapp

# Configure database in .env if needed

#  Run migrations
php artisan migrate

# Set Laravel  permissions

```bash
sudo chown -R $(logname):www-data .
sudo find . -type f -exec chmod 664 {} \;
sudo find . -type d -exec chmod 775 {} \;
````

### .env

```
APP_URL=mpro2.test

LOG_CHANNEL=daily
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
LOG_DAILY_DAYS=2

DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

```

### SQlite managment

```bash
# need RDP X-11

sudo apt install sqlitebrowser
sqlitebrowser

```

#### Setup RDP on Ubuntu Server

```bash
sudo apt update
sudo apt install -y xrdp xfce4

# Set XFCE as the default session
echo xfce4-session > ~/.xsession

# Restart RDP service
sudo systemctl restart xrdp

#
xhost +SI:localuser:root

# On Windows:
# Run "mstsc" (Remote Desktop Connection)
# or use MobaXterm: https://mobaxterm.mobatek.net/download.html
```

### Git key

```bash
ssh-keygen -t ed25519 -C "ваш_email@пример.com"
cat ~/.ssh/id_ed25519.pub
# GitHub →Settings → SSH and GPG keys → New SSH key.
# https://github.com/settings/keys

ssh -T git@github.com
```

### ngnix

```bash
cd /etc/nginx/sites-available конфиги
cd /etc/nginx/ites-enabled - ссылки
sudo ln -s /etc/nginx/sites-available/example.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

MIT © dixiRu
