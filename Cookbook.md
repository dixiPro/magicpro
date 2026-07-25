# MagicPro Cookbook

This cookbook contains practical instructions for installing and developing **MagicPro**, configuring Laravel, managing releases, and preparing the server environment.

## Contents

- [MagicPro](#magicpro)
- [Laravel](#laravel)
- [Nginx](#nginx)

---

## MagicPro

### Development installation

Add the local package repository to the main project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/dixipro/magicpro",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

Install MagicPro from the local repository:

```bash
mkdir -p packages/dixipro
git clone https://github.com/dixiPro/magicpro.git packages/dixipro/magicpro
composer require dixipro/magicpro:@dev
```

Check whether the package is installed as a symbolic link:

```bash
ls -la vendor/dixipro/magicpro
```

If ok

```bash
php artisan migrate

```

Install frontend dependencies:

```bash
cd packages/dixipro/magicpro
npm install
npm run build
```

### Vite

Vite is configured to build assets outside the package directory.

```bash
cd packages/dixipro/magicpro
npm run dev
npm run build
```

### Package maintenance

Remove MagicPro:

```bash
composer remove dixipro/magicpro
```

Check whether the package is installed as a symbolic link:

```bash
ls -la vendor/dixipro/magicpro
```

---

## Laravel

### Install Composer

Skip this section if Composer is already installed.

Download the Composer installer:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
```

Install Composer globally:

```bash
sudo php composer-setup.php \
    --install-dir=/usr/local/bin \
    --filename=composer
```

Remove the installer:

```bash
rm composer-setup.php
```

Check the installed Composer version:

```bash
composer --version
```

### Create a Laravel project

```bash
composer create-project laravel/laravel myapp

cd myapp
```

### Configure permissions

Add the current user to the `www-data` group:

```bash
sudo usermod -aG www-data "$(logname)"
```

Log out and sign in again to apply the new group membership.

Set permissions for Laravel writable directories:

```bash
sudo chgrp -R www-data storage bootstrap/cache

sudo find storage bootstrap/cache \
    -type d \
    -exec chmod 775 {} \;

sudo find storage bootstrap/cache \
    -type f \
    -exec chmod 664 {} \;
```

When using SQLite, make the database directory writable:

```bash
sudo chgrp -R www-data database
sudo chmod -R g+rwX database
```

Create the public storage symbolic link:

```bash
php artisan storage:link
```

### Configure the environment

Configure the application URL, database connection, and logging in `.env`:

```dotenv
APP_ENV=local
APP_URL=http://localhost

LOG_CHANNEL=daily
LOG_LEVEL=debug
LOG_DAILY_DAYS=5
```

Run migrations:

```bash
php artisan migrate
```

Protect the `.env` file after configuration:

```bash
sudo chmod 600 .env
```

---

## Nginx

### Site configuration

Available site configurations are stored in:

```text
/etc/nginx/sites-available
```

Enabled site configurations are stored in:

```text
/etc/nginx/sites-enabled
```

Enable a site by creating a symbolic link:

```bash
sudo ln -s \
    /etc/nginx/sites-available/example.com \
    /etc/nginx/sites-enabled/example.com
```

Check the Nginx configuration:

```bash
sudo nginx -t
```

Reload Nginx:

```bash
sudo systemctl reload nginx
```

### HTTPS with Certbot

Install Certbot and the Nginx plugin:

```bash
sudo apt install -y certbot python3-certbot-nginx
```

Create a certificate for one domain:

```bash
sudo certbot --nginx -d example.com
```

Create a certificate for the root domain and the `www` subdomain:

```bash
sudo certbot --nginx \
    -d example.com \
    -d www.example.com
```

Test automatic certificate renewal:

```bash
sudo certbot renew --dry-run
```

---

## License

MIT © dixiRu
