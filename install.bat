@echo off
echo ========================================
echo  INSTALACION PROYECTO FACTURACION 
echo  ELECTRONICA MULTI-PAIS
echo ========================================
echo.

echo Instalando dependencias de Composer...
composer install --ignore-platform-req=ext-sodium

echo.
echo Copiando archivo de configuracion...
if not exist .env (
    copy .env.example .env
    echo Archivo .env creado desde .env.example
) else (
    echo Archivo .env ya existe
)

echo.
echo Generando clave de aplicacion...
php artisan key:generate

echo.
echo Ejecutando migraciones...
php artisan migrate

echo.
echo Publicando configuracion JWT...
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"

echo.
echo Generando clave JWT...
php artisan jwt:secret

echo.
echo ========================================
echo  INSTALACION COMPLETADA
echo ========================================
echo.
echo Para iniciar el servidor ejecuta:
echo php artisan serve
echo.
echo Para testing con Postman, importa:
echo postman-collection.json
echo.
echo Para documentacion completa, lee:
echo README-PARAGUAY.md
echo.
pause
