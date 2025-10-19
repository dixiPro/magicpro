**Установить пакет**

```
composer config repositories.magicpro path packages/dixi/magicpro

jq '.repositories.magicpro.options.symlink=true' composer.json > composer.tmp && mv composer.tmp composer.json

composer require magicpro/magicpro:"^0.1.0"

php artisan migrate

php artisan magicpro:install
```

** установить **
composer require friendsofphp/php-cs-fixer

**папки в Ларавеле**

```
sudo chown -R $USER:www-data .
sudo find . -type f -exec chmod 664 {} \;
sudo find . -type d -exec chmod 775 {} \;
```

**Альтернатива**

```
sudo chmod -R 777 ./storage
sudo chmod -R 777 ./bootstrap/cache/
```

**помогает**

```
rm composer.lock
composer install
composer dump-autoload -o

в composer.json (проекта дописать)

    "repositories": [
        {
            "type": "path",
            "url": "magicPro"
        }
    ]

```

php artisan optimize:clear
php artisan package:discover

composer dump-autoload
composer clear-cache

php artisan vendor:publish --tag=magic-source --force
npm run build

vite.config.js (в корне проекта, один общий Vite)
import { defineConfig } from 'vite'
import fs from 'node:fs'
import laravel from 'laravel-vite-plugin'

const devPath = 'packages/Vendor/Magic/admin/js/editor.js'
const pubPath = 'resources/vendor/magic/admin/js/editor.js'

const input = fs.existsSync(devPath) ? devPath : pubPath

export default defineConfig({
server: {
// разрешаем читать из packages/ во время dev
fs: { allow: ['.', 'packages'] },
},
plugins: [
laravel({
input: [input],
refresh: true,
}),
],
})

Создать миграцию
php artisan make:migration create_articles_table --path=packages/vendor/magicPro/database/migrations

    "repositories": [
        {
            "type": "path",
            "url": "packages/vendor/magicPro",
            "options": {
                "symlink": true
            }
        }
    ]

home-soln-myApp-public-index.php
home root/root
soln soln/soln
myApp soln/soln  
public soln/www-data

index.php 777

выполняется от группы www-data
index.php не работает
Но ведь

но если ставим
myApp myApp/www-data  
Работает.

Т.е.

почему myApp должен иметь группу www-data? Без ларавела. Только линукс
