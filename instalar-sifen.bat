@echo off
echo ============================================
echo    🇵🇾 INSTALACION SIFEN PARAGUAY v1.0
echo ============================================
echo.

:: Verificar si estamos en el directorio correcto
if not exist "artisan" (
    echo ❌ Error: Este script debe ejecutarse desde el directorio raiz del proyecto Laravel
    echo    Asegurese de estar en la carpeta que contiene el archivo 'artisan'
    pause
    exit /b 1
)

echo 📋 Verificando requisitos del sistema...
echo.

:: Verificar PHP
php -v >nul 2>&1
if errorlevel 1 (
    echo ❌ Error: PHP no esta instalado o no esta en el PATH
    echo    Instale PHP 8.1+ y agregue al PATH del sistema
    pause
    exit /b 1
) else (
    echo ✅ PHP instalado
)

:: Verificar Composer
composer --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Error: Composer no esta instalado o no esta en el PATH
    echo    Instale Composer desde https://getcomposer.org/
    pause
    exit /b 1
) else (
    echo ✅ Composer instalado
)

:: Verificar extensiones PHP requeridas
echo.
echo 🔍 Verificando extensiones PHP...
php -m | findstr /i "openssl" >nul
if errorlevel 1 (
    echo ❌ Error: Extension OpenSSL no disponible
    set "missing_ext=1"
) else (
    echo ✅ OpenSSL disponible
)

php -m | findstr /i "soap" >nul
if errorlevel 1 (
    echo ❌ Error: Extension SOAP no disponible
    set "missing_ext=1"
) else (
    echo ✅ SOAP disponible
)

php -m | findstr /i "xml" >nul
if errorlevel 1 (
    echo ❌ Error: Extension XML no disponible
    set "missing_ext=1"
) else (
    echo ✅ XML disponible
)

if defined missing_ext (
    echo.
    echo ❌ Faltan extensiones PHP requeridas
    echo    Configure PHP con las extensiones necesarias
    pause
    exit /b 1
)

echo.
echo 🚀 Iniciando instalacion...
echo.

:: Crear directorios necesarios
echo 📁 Creando directorios...
if not exist "storage\certificates" mkdir "storage\certificates"
if not exist "storage\sifen" mkdir "storage\sifen"
if not exist "storage\sifen\xml" mkdir "storage\sifen\xml"
if not exist "storage\sifen\pdf" mkdir "storage\sifen\pdf"
if not exist "storage\sifen\logs" mkdir "storage\sifen\logs"
if not exist "storage\sifen\temp" mkdir "storage\sifen\temp"
echo ✅ Directorios creados

:: Configurar permisos (solo en Windows con icacls)
echo 🔒 Configurando permisos...
icacls "storage" /grant "Users:(OI)(CI)F" /T >nul 2>&1
icacls "bootstrap\cache" /grant "Users:(OI)(CI)F" /T >nul 2>&1
echo ✅ Permisos configurados

:: Instalar dependencias
echo 📦 Instalando dependencias de Composer...
composer install --no-dev --optimize-autoloader
if errorlevel 1 (
    echo ❌ Error al instalar dependencias
    pause
    exit /b 1
)
echo ✅ Dependencias instaladas

:: Verificar archivo .env
echo ⚙️ Configurando archivo de entorno...
if not exist ".env" (
    if exist ".env.example" (
        copy ".env.example" ".env"
        echo ✅ Archivo .env creado desde .env.example
    ) else (
        echo ❌ Error: No se encontro .env.example
        pause
        exit /b 1
    )
) else (
    echo ✅ Archivo .env ya existe
)

:: Generar clave de aplicacion
echo 🔑 Generando clave de aplicacion...
php artisan key:generate --force
if errorlevel 1 (
    echo ❌ Error al generar clave de aplicacion
    pause
    exit /b 1
)
echo ✅ Clave de aplicacion generada

:: Generar clave JWT
echo 🔐 Generando clave JWT...
php artisan jwt:secret --force
if errorlevel 1 (
    echo ⚠️ Advertencia: No se pudo generar clave JWT (puede ser normal si no esta configurado)
)

:: Ejecutar migraciones
echo 🗄️ Ejecutando migraciones de base de datos...
echo.
echo NOTA: Asegurese de que la base de datos este configurada en .env
echo.
set /p "run_migrations=¿Ejecutar migraciones ahora? (s/n): "
if /i "%run_migrations%"=="s" (
    php artisan migrate --force
    if errorlevel 1 (
        echo ❌ Error en migraciones - verifique configuracion de base de datos
        echo    Puede ejecutar 'php artisan migrate' manualmente mas tarde
    ) else (
        echo ✅ Migraciones ejecutadas
    )
) else (
    echo ⏳ Migraciones omitidas - ejecute 'php artisan migrate' manualmente
)

:: Configuracion SIFEN
echo.
echo 🇵🇾 Configuracion SIFEN Paraguay...
php artisan sifen:setup
if errorlevel 1 (
    echo ⚠️ Advertencia: Error en configuracion SIFEN
)

echo.
echo ============================================
echo    ✅ INSTALACION COMPLETADA
echo ============================================
echo.
echo 📋 Proximos pasos:
echo.
echo 1. Configure la base de datos en .env si no lo ha hecho
echo 2. Ejecute: php artisan migrate (si no se ejecuto)
echo 3. Configure su empresa: php artisan sifen:company
echo 4. Coloque su certificado P12 en: storage\certificates\
echo 5. Pruebe el sistema: php artisan sifen:test
echo 6. Acceda al dashboard: http://su-dominio/sifen/dashboard
echo.
echo 📚 Documentacion completa en: MANUAL-INSTALACION.md
echo.
echo ¡Sistema SIFEN Paraguay listo para usar!
echo.
pause
