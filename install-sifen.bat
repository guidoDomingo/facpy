@echo off
echo ===============================================
echo    SIFEN Paraguay - Script de Instalacion
echo ===============================================
echo.

echo [1/6] Instalando dependencias de Composer...
composer install --ignore-platform-req=ext-sodium
if %errorlevel% neq 0 (
    echo Error instalando dependencias de Composer
    pause
    exit /b 1
)

echo.
echo [2/6] Ejecutando migraciones de base de datos...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo Error ejecutando migraciones
    pause
    exit /b 1
)

echo.
echo [3/6] Limpiando cache de la aplicacion...
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo.
echo [4/6] Creando directorios necesarios...
if not exist "storage\certificates" mkdir storage\certificates
if not exist "storage\app\public\qr_codes" mkdir storage\app\public\qr_codes
if not exist "storage\app\public\kude" mkdir storage\app\public\kude

echo.
echo [5/6] Configurando permisos de directorios...
echo Asegurese de que los siguientes directorios tengan permisos de escritura:
echo - storage\certificates
echo - storage\app\public\qr_codes
echo - storage\app\public\kude
echo - storage\logs

echo.
echo [6/6] Verificando configuracion...
echo Revisando archivo .env...

if not exist ".env" (
    echo ADVERTENCIA: Archivo .env no encontrado
    echo Copie .env.example a .env y configure sus variables
)

echo.
echo ===============================================
echo    INSTALACION COMPLETADA
echo ===============================================
echo.
echo PROXIMOS PASOS:
echo 1. Configure su archivo .env con los datos de su base de datos
echo 2. Configure las URLs de SIFEN (test/produccion)
echo 3. Suba los certificados P12 de sus empresas
echo 4. Acceda al dashboard en: http://localhost/sifen
echo.
echo ENDPOINTS API PRINCIPALES:
echo - POST /api/sifen/create-nce (Crear NCE)
echo - POST /api/sifen/cancel-document (Cancelar documento)
echo - POST /api/sifen/generate-kude (Generar KuDE)
echo - POST /api/sifen/send-batch (Envio en lote)
echo.
echo Para mas informacion consulte la documentacion.
echo.
pause
