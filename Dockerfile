# syntax=docker/dockerfile:1

############################################
# Etapa 1 - Dependencias PHP
############################################
FROM php:8.4-cli AS vendor

WORKDIR /app

# Dependencias de sistema necesarias solo para compilar extensiones/Composer.
# --no-install-recommends reduce paquetes innecesarios.
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        zip \
        pdo \
        pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Solo los manifiestos primero: si no cambian, esta capa (y composer install)
# se reutiliza de caché aunque cambie el código fuente.
COPY composer.json composer.lock ./

# Cache mount de BuildKit para el cache interno de Composer: acelera builds
# repetidos sin tener que copiar vendor/ entre builds.
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --optimize-autoloader

############################################
# Etapa 2 - Compilar Assets
############################################
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./

# Cache mount para node_modules descargados por npm, no para node_modules final.
RUN --mount=type=cache,target=/root/.npm \
    npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
# COPY postcss.config.js ./
# COPY tailwind.config.js ./

RUN npm run build

############################################
# Etapa 3 - Aplicación
############################################
FROM php:8.4-apache AS app

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pcntl \
        pdo \
        pdo_pgsql \
        xml \
        zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Configuración de OPcache para producción (mejora notable de rendimiento).
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.jit=tracing'; \
        echo 'opcache.jit_buffer_size=64M'; \
    } > /usr/local/etc/php/conf.d/opcache-prod.ini

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Copiar el código de la app (respetando .dockerignore: ver nota abajo)
COPY . .

# Evita que Laravel intente usar el dev server de Vite en producción.
RUN rm -f public/hot

# Dependencias PHP y assets ya compilados en etapas anteriores.
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Healthcheck opcional: útil para detectar contenedores "vivos" pero rotos.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/start.sh"]
