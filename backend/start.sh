#!/bin/sh
set -e

cd /var/www

# Wait for database to be ready
echo "Waiting for database..."
timeout=60
while ! php -r "new PDO('pgsql:host=${DB_HOST:-db};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-laravel}', '${DB_USERNAME:-postgres}', '${DB_PASSWORD:-secret}');" 2>/dev/null; do
    timeout=$((timeout - 2))
    if [ $timeout -le 0 ]; then
        echo "Database connection timeout"
        exit 1
    fi
    sleep 2
done
echo "Database is ready!"

# Run migrations at startup
php artisan migrate:fresh --seed

# Start PHP-FPM
exec php-fpm
