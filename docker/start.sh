#!/usr/bin/env sh
set -e

cd /var/www/html

# Si no existe .env, usar .env.example
if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

echo "Esperando conexión a la base de datos..."
sleep 5

echo "Ejecutando migraciones..."
php artisan migrate --force

echo "Creando enlace de storage..."
php artisan storage:link >/dev/null 2>&1 || true

if [ "${APP_ENV}" = "production" ]; then
    echo "Generando cachés..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "Iniciando Apache..."
exec apache2-foreground
