#!/usr/bin/env sh
set -e

cd /var/www/html

# Si no existe .env, usar .env.example
if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

echo "Esperando conexión a la base de datos..."
sleep 5

# --- Migraciones / Reset de base de datos ---
# DB_FRESH_SEED=true borra TODAS las tablas y las recrea desde cero,
# luego carga todos los seeders. Es destructivo: solo debe activarse
# a propósito (variable de entorno en Render) y desactivarse después
# del deploy para que no se repita en el siguiente release.
if [ "${DB_FRESH_SEED}" = "true" ]; then
    echo "⚠️  DB_FRESH_SEED=true detectado: reseteando base de datos desde cero..."
    php artisan migrate:fresh --force --seed
else
    echo "Ejecutando migraciones..."
    php artisan migrate --force
fi

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
