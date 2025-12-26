# MagicPro Cook Book

### ⚙️ Installation

```bash
# in project root
composer require dixipro/magicpro
php artisan magicpro:install
sudo chown -R :www-data dataMagicPro
php artisan migrate
```

### ⚙️ Installation for dev

Add to main composer

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
npm run dev
npm run build
```

## Laravel installation

```bash
# Install Composer (if not installed)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Check version
composer -V

# Create a new Laravel project
composer create-project laravel/laravel myapp

# Go to the project folder
cd myapp
# Set Laravel  permissions
# add user to  www-data group
sudo usermod -a -G www-data $(logname)
#
sudo chgrp -R www-data storage bootstrap/cache public database
sudo find . -type d -exec chmod 775 {} \;
sudo find . -type f -exec chmod 664 {} \;
#
php artisan storage:link
#
# Configure database in .env
# APP_URL
# database
# log
#
# APP_ENV=local
# LOG_CHANNEL=daily
# LOG_LEVEL=debug
# LOG_DAILY_DAYS=5

# Run migrations
php artisan migrate
# after all
sudo chmod 600 .env
```

## install livewire

```bash
composer require livewire/livewire
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

#certbot
apt install certbot python3-certbot-nginx -y
certbot --nginx -d new.magalif.ru
certbot renew --dry-run

sudo certbot --nginx -d site.com -d www.site.com

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
