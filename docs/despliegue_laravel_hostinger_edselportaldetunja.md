# Despliegue de Laravel en Hostinger — edselportaldetunja.com

## 1. Objetivo

Desplegar el proyecto Laravel `colserdev_islas_erp` en Hostinger usando:

- Dominio: `edselportaldetunja.com`
- PHP: 8.4.19
- Composer: 2.9.8
- Git: 2.43.7
- MySQL
- Laravel
- Vite con assets compilados localmente

El proyecto quedó alojado en:

```text
/home/u578168976/colserdev_islas_erp
```

---

## 2. Verificación inicial del servidor

Conexión SSH:

```bash
whoami
pwd
```

Usuario:

```text
u578168976
```

Home:

```text
/home/u578168976
```
ssh -p 65002 u578168976@212.1.209.40


Versiones verificadas:

```bash
php -v
composer --version
git --version
```

Resultado relevante:

```text
PHP 8.4.19
Composer 2.9.8
Git 2.43.7
```

Node/NPM no estaban disponibles en el servidor:

```bash
node -v
npm -v
```

Por este motivo, los assets de Vite se generaron antes/localmente y se subieron al repositorio.

---

## 3. Compilación de Vite

El proyecto ya tenía generado:

```text
public/build/
├── manifest.json
└── assets/
```

La compilación local produjo, entre otros:

```text
public/build/manifest.json
public/build/assets/app-*.css
public/build/assets/app-*.js
public/build/assets/bootstrap-icons-*.woff
public/build/assets/bootstrap-icons-*.woff2
```

Por tanto, no fue necesario ejecutar `npm install` ni `npm run build` en Hostinger.

Importante: `public/build` debe estar versionado/subido al repositorio si el servidor no tiene Node/NPM.

---

## 4. Clonación del proyecto

Desde el home:

```bash
cd ~
git clone https://github.com/direinar/colserdev_islas_erp.git
```

Proyecto:

```text
/home/u578168976/colserdev_islas_erp
```

Verificación:

```bash
cd ~/colserdev_islas_erp
ls -la
```

---

## 5. Instalación de Composer en Hostinger

Hostinger tenía deshabilitada la función PHP `proc_open`.

Comprobación:

```bash
php -i | grep disable_functions
```

Resultado incluía:

```text
proc_open
exec
shell_exec
passthru
symlink
...
```

También:

```bash
php -r "var_dump(function_exists('proc_open'));"
```

Resultado:

```text
bool(false)
```

### Solución

Composer se ejecutó sin scripts:

```bash
composer install --no-dev --optimize-autoloader --no-scripts
```

Esto permitió instalar las dependencias sin ejecutar automáticamente:

```text
@php artisan package:discover
```

que era lo que provocaba el error relacionado con `proc_open`.

---

## 6. Corrección PSR-4

Durante Composer apareció:

```text
Class App\Livewire\Planilla located in ./app/Livewire/planilla.php
does not comply with psr-4 autoloading standard
```

En Linux el nombre del archivo debe coincidir con la clase respetando mayúsculas/minúsculas.

Se corrigió:

```bash
mv app/Livewire/planilla.php app/Livewire/Planilla.php
```

Después:

```bash
composer dump-autoload --no-scripts -o
```

---

## 7. Configuración de `.env`

Se creó el archivo:

```bash
cp .env.example .env
```

Configuración de producción utilizada como base:

```env
APP_NAME="ByH Agrocomercial SAS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://edselportaldetunja.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

DB_CONNECTION=mysql
DB_HOST=HOST_MYSQL_DE_HOSTINGER
DB_PORT=3306
DB_DATABASE=BASE_DE_DATOS_DE_HOSTINGER
DB_USERNAME=USUARIO_MYSQL_DE_HOSTINGER
DB_PASSWORD=CONTRASEÑA_MYSQL

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="no-reply@edselportaldetunja.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

### Importante

No utilizar:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://gestion_islas.test
```

en producción.

La configuración final debe tener:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://edselportaldetunja.com
```

---

## 8. Clave de Laravel

Después de crear/configurar `.env`:

```bash
php artisan key:generate
```

Esto genera la `APP_KEY` específica para producción.

No es recomendable publicar ni compartir el contenido de `APP_KEY`.

---

## 9. Base de datos MySQL

La aplicación utiliza:

```env
DB_CONNECTION=mysql
```

La base de datos de producción se creó desde cero en Hostinger.

No se importó la base de datos local `db_gestion_islas`.

### Verificación de conexión

Se ejecutó:

```bash
php artisan migrate:status
```

Inicialmente apareció:

```text
ERROR  Migration table not found.
```

Esto confirmó que Laravel sí podía conectarse a MySQL y que la base de datos estaba vacía.

Para una instalación nueva, se pueden ejecutar:

```bash
php artisan migrate --force
```

Y posteriormente, si corresponde:

```bash
php artisan db:seed --force
```

o:

```bash
php artisan migrate --seed --force
```

Antes de ejecutar seeders en producción conviene revisar `database/seeders/DatabaseSeeder.php`.

---

## 10. Document Root de Hostinger

Hostinger no permitió modificar el Document Root del dominio.

El dominio utiliza:

```text
/home/u578168976/domains/edselportaldetunja.com/public_html
```

La estructura inicial era:

```text
domains/
└── edselportaldetunja.com/
    └── public_html/
        └── default.php
```

La aplicación Laravel está en:

```text
/home/u578168976/colserdev_islas_erp
```

y su carpeta pública es:

```text
/home/u578168976/colserdev_islas_erp/public
```

### Solución aplicada

Se conservó la carpeta original:

```bash
cd ~/domains/edselportaldetunja.com
mv public_html public_html_backup
```

Luego se creó un enlace simbólico:

```bash
ln -s /home/u578168976/colserdev_islas_erp/public public_html
```

Resultado:

```text
public_html -> /home/u578168976/colserdev_islas_erp/public
```

Esto permite que el dominio sirva únicamente la carpeta `public` de Laravel sin exponer directamente:

```text
app/
config/
database/
resources/
routes/
storage/
vendor/
.env
```

---

## 11. Resultado del dominio

El flujo quedó:

```text
https://edselportaldetunja.com
        ↓
/home/u578168976/domains/edselportaldetunja.com/public_html
        ↓
/home/u578168976/colserdev_islas_erp/public
        ↓
public/index.php
        ↓
Laravel
```

El login de la aplicación quedó accesible y se pudo iniciar sesión correctamente.

---

## 12. `storage:link` en Hostinger

El comando estándar:

```bash
php artisan storage:link
```

falló con:

```text
Call to undefined function Illuminate\Filesystem\exec()
```

Esto ocurrió porque Hostinger tiene deshabilitadas funciones como:

```text
exec
symlink
proc_open
```

### Solución manual

Se verificó que existiera:

```text
storage/app/public
```

Luego se creó manualmente el enlace:

```bash
ln -s /home/u578168976/colserdev_islas_erp/storage/app/public \
/home/u578168976/colserdev_islas_erp/public/storage
```

Verificación:

```bash
ls -la public | grep storage
```

Resultado:

```text
storage -> /home/u578168976/colserdev_islas_erp/storage/app/public
```

Por tanto, los archivos públicos almacenados allí pueden ser servidos mediante:

```text
https://edselportaldetunja.com/storage/archivo.ext
```

No es necesario volver a ejecutar:

```bash
php artisan storage:link
```

en este servidor mientras el enlace manual exista.

---

## 13. Permisos

Para Laravel se deben garantizar permisos de escritura sobre:

```text
storage/
bootstrap/cache/
```

Comandos:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

Verificación:

```bash
ls -ld storage bootstrap/cache
```

---

## 14. Optimización de Laravel

Con `.env` configurado para producción:

```bash
php artisan optimize
```

Esto permite generar las cachés necesarias para producción.

Si se necesita limpiar posteriormente las cachés:

```bash
php artisan optimize:clear
```

y luego:

```bash
php artisan optimize
```

---

## 15. Estado final alcanzado

Hasta este punto:

```text
✅ Dominio configurado
✅ Dominio muestra la aplicación Laravel
✅ PHP 8.4.19
✅ Composer instalado
✅ Git instalado
✅ Proyecto clonado
✅ Dependencias Composer instaladas
✅ `proc_open` identificado como restringido
✅ Composer ejecutado con `--no-scripts`
✅ Problema PSR-4 de Planilla corregido
✅ Vite compilado localmente
✅ `public/build` disponible
✅ MySQL conectado
✅ Base de datos nueva
✅ Login funcionando
✅ `public_html` enlazado a Laravel/public
✅ Storage link creado manualmente
```

---

# 16. Configuraciones pendientes/recomendadas para producción

## Correo

Actualmente:

```env
MAIL_MAILER=log
```

Esto significa que Laravel no está enviando correos reales; los registra en los logs.

Para producción se recomienda configurar SMTP, por ejemplo:

```env
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@edselportaldetunja.com
MAIL_FROM_NAME="${APP_NAME}"
```

Los valores deben ser los proporcionados por el servicio de correo.

---

## Colas

El proyecto utiliza:

```env
QUEUE_CONNECTION=database
```

Por lo tanto, debe existir la infraestructura de jobs en la base de datos y debe existir un proceso que ejecute:

```bash
php artisan queue:work
```

En hosting compartido normalmente esto se configura mediante Cron Jobs o el mecanismo de tareas programadas disponible en hPanel.

---

## Scheduler

Si el proyecto utiliza tareas programadas de Laravel, configurar un Cron Job para:

```bash
php artisan schedule:run
```

normalmente cada minuto.

La ruta absoluta debe apuntar al proyecto:

```text
/home/u578168976/colserdev_islas_erp/artisan schedule:run
```

La forma exacta depende del panel de Hostinger.

---

## Backups

Configurar backups periódicos de:

1. Base de datos MySQL.
2. Archivos de `storage/app`.
3. Código/configuración si se realizan cambios fuera de Git.

No depender exclusivamente de la copia de trabajo del servidor.

---

## Seguridad

Mantener:

```env
APP_ENV=production
APP_DEBUG=false
```

Nunca publicar:

```text
.env
APP_KEY
credenciales de MySQL
credenciales SMTP
tokens API
```

Mantener el proyecto fuera de `public_html`, exponiendo solamente:

```text
colserdev_islas_erp/public
```

---

# 17. Comandos principales de mantenimiento

### Actualizar código desde Git

```bash
cd ~/colserdev_islas_erp
git pull origin main
```

Después:

```bash
composer install --no-dev --optimize-autoloader --no-scripts
```

Si cambian las migraciones:

```bash
php artisan migrate --force
```

Y posteriormente:

```bash
php artisan optimize
```

### Limpiar cachés

```bash
php artisan optimize:clear
```

### Volver a optimizar

```bash
php artisan optimize
```

### Ver estado de migraciones

```bash
php artisan migrate:status
```

### Ver versión Laravel

```bash
php artisan --version
```

### Ver versión PHP

```bash
php -v
```

---

# 18. Advertencias importantes

No ejecutar en producción:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
```

salvo que se quiera deliberadamente eliminar/recrear la estructura de la base de datos.

Tampoco ejecutar seeders de datos de prueba sin revisar previamente qué insertan.

No copiar todo Laravel dentro de `public_html`.

La estructura correcta es:

```text
/home/u578168976/
├── colserdev_islas_erp/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
│
└── domains/
    └── edselportaldetunja.com/
        ├── public_html -> /home/u578168976/colserdev_islas_erp/public
        └── public_html_backup/
```

---

# 19. Comandos de referencia para una nueva instalación

```bash
cd ~

git clone https://github.com/direinar/colserdev_islas_erp.git

cd ~/colserdev_islas_erp

composer install --no-dev --optimize-autoloader --no-scripts

mv app/Livewire/planilla.php app/Livewire/Planilla.php

composer dump-autoload --no-scripts -o

cp .env.example .env

nano .env

php artisan key:generate

php artisan migrate --force

chmod -R 775 storage
chmod -R 775 bootstrap/cache

ln -s /home/u578168976/colserdev_islas_erp/storage/app/public \
/home/u578168976/colserdev_islas_erp/public/storage

php artisan optimize
```

Si se necesitan seeders:

```bash
php artisan db:seed --force
```

---

## 20. Información del despliegue

**Aplicación:** ByH Agrocomercial SAS  
**Dominio:** https://edselportaldetunja.com  
**Proyecto:** `colserdev_islas_erp`  
**Repositorio:** `direinar/colserdev_islas_erp`  
**Servidor:** Hostinger  
**PHP:** 8.4.19  
**Base de datos:** MySQL  
**Document Root efectivo:** `/home/u578168976/colserdev_islas_erp/public`  
**Ruta del proyecto:** `/home/u578168976/colserdev_islas_erp`

