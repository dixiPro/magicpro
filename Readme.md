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

### ⚙️ Installation (1)

```bash
# in project root
composer require dixipro/magicpro
php artisan magicpro:install
sudo chown -R :www-data dataMagicPro
php artisan migrate
```

### ⚙️ OR! installation from git (2)

в основной композер добавить

```json
"repositories": [
  {
    "type": "path",
    "url": "packages/dixipro/magicpro",
    "options":{
      "copy": true
   }
  }
]
```

```bash
# install magicpro
git clone https://github.com/dixiRu/magicpro packages/dixipro/magicpro
composer require dixipro/magicpro
php artisan magicpro:install
sudo chown -R :www-data dataMagicPro
php artisan migrate

##
```

## ⚙️ Installation for dev

в основной композер добавить

```json
"repositories": [
  {
    "type": "path",
    "url": "packages/dixipro/magicpro",
    "options":{
      "symlink": true
   }
  }
]
```

```bash
git clone https://github.com/dixiRu/magicpro packages/dixipro/magicpro
composer require dixipro/magicpro
php artisan magicpro:install
sudo chown -R :www-data dataMagicPro
php artisan migrate
##
cd packages/dixipro/magicpro
# install fro development
npm i
```

**Vite build**

Vite is configured to build outside the project root.

```bash
cd packages/dixipro/magicpro
npm i
npm run dev
npm run build
```

### Added / Change

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

### Laravel installation

````bash
# Install Composer (if not installed)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Check version
composer -V

# Create a new Laravel project
composer create-project laravel/laravel myapp

# Go to the project folder
cd myapp

# Configure database in .env if needed
#  Run migrations
php artisan migrate

# Set Laravel  permissions

```bash
# юзер в группу www-data
sudo usermod -a -G www-data $(logname)
#
sudo find . -type f -exec chmod 664 {} \;
sudo find . -type d -exec chmod 775 {} \;
#
sudo chgrp -R www-data storage bootstrap/cache public
sudo chmod 600 .env
#
sudo chgrp -R www-data database


#?
php artisan storage:link
#?
sudo chown -R $(logname):www-data .
````

# install livewire

```bash
composer require livewire/livewire
```

### change .env

```bash
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

### Git cookbook

```bash
#generate key
ssh-keygen -t ed25519 -C "ваш_email@пример.com"
cat ~/.ssh/id_ed25519.pub
# GitHub →Settings → SSH and GPG keys → New SSH key.
# https://github.com/settings/keys

ssh -T git@github.com
```

````bash
# del local tags
git tag -l | xargs git tag -d
# del repo tags
git push origin --delete $(git tag -l)
# add tagh
git tag 1.0.1
git push origin 1.0.1
```

### SQlite managment

```bash
# need RDP X-11

sudo apt install sqlitebrowser
sqlitebrowser

````

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

### nginx cookbook

```bash
# configs
cd /etc/nginx/sites-available
# active sites
cd /etc/nginx/sites-enabled
# make link
sudo ln -s /etc/nginx/sites-available/example.com /etc/nginx/sites-enabled/
# check
sudo nginx -t
# restart
sudo systemctl reload nginx
```

### cookbook difff

```bash
#remove
composer remove dixipro/magicpro
rm -rf vendor/dixipro composer.lock
composer clear-cache
composer require dixipro/magicpro:dev-main
composer require dixipro/magicpro

# see link page
ls -la vendor/dixipro/magicpro
```

MIT © dixiRu
