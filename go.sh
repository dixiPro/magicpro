#!/bin/bash

PHP_FPM="php8.3-fpm"   # при необходимости поменяйте версию

clear
echo "===== DEV MENU ====="
echo "1) Очистить весь кеш Laravel"
echo "2) Включить Xdebug"
echo "3) Выключить Xdebug"
echo "4) Рестарт PHP-FPM"
echo "5) Рестарт Nginx"
echo "6) Включить MySQL"
echo "7) Выключить MySQL"
echo "8) Включить PostgreSQL"
echo "9) Выключить PostgreSQL"
echo "0) Выход"
echo "===================="
read -p "Выбор: " choice

case $choice in
  1)
    php artisan optimize:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    echo "✔ Laravel кеш полностью очищен"
    ;;
  2)
    sudo phpenmod xdebug
    sudo systemctl restart $PHP_FPM
    echo "✔ Xdebug включён"
    ;;
  3)
    sudo phpdismod xdebug
    sudo systemctl restart $PHP_FPM
    echo "✔ Xdebug выключен"
    ;;
  4)
    sudo systemctl restart $PHP_FPM
    echo "✔ PHP-FPM перезапущен"
    ;;
  5)
    sudo systemctl restart nginx
    echo "✔ Nginx перезапущен"
    ;;
  6)
    sudo systemctl start mysql
    echo "✔ MySQL запущен"
    ;;
  7)
    sudo systemctl stop mysql
    echo "✔ MySQL остановлен"
    ;;
  8)
    sudo systemctl start postgresql
    echo "✔ PostgreSQL запущен"
    ;;
  9)
    sudo systemctl stop postgresql
    echo "✔ PostgreSQL остановлен"
    ;;
  0)
    echo "Выход"
    ;;
  *)
    echo "✖ Неверный выбор"
    ;;
esac
