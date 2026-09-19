#!/bin/sh
# Dijalankan setiap container "app" dinyalakan.
set -e
cd /var/www/html

if [ ! -f .env ]; then
    echo "ERROR: file .env tidak ditemukan di folder proyek NAS. Buat dari docker/env.nas.example." >&2
    exit 1
fi

# Folder storage di-mount dari NAS (awalnya kosong) — buat struktur yang dibutuhkan Laravel.
mkdir -p storage/app/public storage/app/lo-profile storage/app/tmp storage/fonts \
         storage/framework/cache/data storage/framework/sessions storage/framework/views \
         storage/logs

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan migrate --force
php artisan optimize

chown -R www-data:www-data storage bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
