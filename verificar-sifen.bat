@echo off
echo ============================================
echo   🔍 VERIFICACION SISTEMA SIFEN PARAGUAY
echo ============================================
echo.

:: Verificar directorio del proyecto
if not exist "artisan" (
    echo ❌ Error: Este script debe ejecutarse desde el directorio raiz del proyecto Laravel
    pause
    exit /b 1
)

echo 📋 Verificando estado del sistema...
echo.

:: Variables para conteo
set "checks_passed=0"
set "total_checks=0"

:: Verificar PHP y extensiones
echo 🔍 PHP y Extensiones:
set /a total_checks+=1
php -v >nul 2>&1
if errorlevel 1 (
    echo   ❌ PHP no disponible
) else (
    echo   ✅ PHP instalado
    set /a checks_passed+=1
)

set /a total_checks+=1
php -m | findstr /i "openssl" >nul
if errorlevel 1 (
    echo   ❌ Extension OpenSSL faltante
) else (
    echo   ✅ Extension OpenSSL
    set /a checks_passed+=1
)

set /a total_checks+=1
php -m | findstr /i "soap" >nul
if errorlevel 1 (
    echo   ❌ Extension SOAP faltante
) else (
    echo   ✅ Extension SOAP
    set /a checks_passed+=1
)

:: Verificar archivos criticos
echo.
echo 📁 Archivos del Sistema:
set /a total_checks+=1
if exist ".env" (
    echo   ✅ Archivo .env existe
    set /a checks_passed+=1
) else (
    echo   ❌ Archivo .env faltante
)

set /a total_checks+=1
if exist "vendor\autoload.php" (
    echo   ✅ Dependencias Composer instaladas
    set /a checks_passed+=1
) else (
    echo   ❌ Dependencias Composer faltantes
)

:: Verificar directorios SIFEN
echo.
echo 🗂️ Directorios SIFEN:
set /a total_checks+=1
if exist "storage\certificates" (
    echo   ✅ Directorio certificados
    set /a checks_passed+=1
) else (
    echo   ❌ Directorio certificados faltante
)

set /a total_checks+=1
if exist "storage\sifen" (
    echo   ✅ Directorio SIFEN
    set /a checks_passed+=1
) else (
    echo   ❌ Directorio SIFEN faltante
)

:: Verificar servicios SIFEN
echo.
echo 🔧 Servicios SIFEN:
set /a total_checks+=1
if exist "app\Services\SifenWebService.php" (
    echo   ✅ SifenWebService
    set /a checks_passed+=1
) else (
    echo   ❌ SifenWebService faltante
)

set /a total_checks+=1
if exist "app\Services\SifenCertificateService.php" (
    echo   ✅ SifenCertificateService
    set /a checks_passed+=1
) else (
    echo   ❌ SifenCertificateService faltante
)

set /a total_checks+=1
if exist "app\Services\SifenKudeService.php" (
    echo   ✅ SifenKudeService
    set /a checks_passed+=1
) else (
    echo   ❌ SifenKudeService faltante
)

:: Verificar controladores
echo.
echo 🎮 Controladores:
set /a total_checks+=1
if exist "app\Http\Controllers\SifenAdvancedController.php" (
    echo   ✅ SifenAdvancedController
    set /a checks_passed+=1
) else (
    echo   ❌ SifenAdvancedController faltante
)

set /a total_checks+=1
if exist "app\Http\Controllers\SifenDashboardController.php" (
    echo   ✅ SifenDashboardController
    set /a checks_passed+=1
) else (
    echo   ❌ SifenDashboardController faltante
)

:: Verificar modelos
echo.
echo 📊 Modelos de Datos:
set /a total_checks+=1
if exist "app\Models\ElectronicDocument.php" (
    echo   ✅ ElectronicDocument
    set /a checks_passed+=1
) else (
    echo   ❌ ElectronicDocument faltante
)

set /a total_checks+=1
if exist "app\Models\DocumentEvent.php" (
    echo   ✅ DocumentEvent
    set /a checks_passed+=1
) else (
    echo   ❌ DocumentEvent faltante
)

:: Verificar comandos Artisan
echo.
echo ⚙️ Comandos Artisan:
set /a total_checks+=1
if exist "app\Console\Commands\SifenSetupCommand.php" (
    echo   ✅ SifenSetupCommand
    set /a checks_passed+=1
) else (
    echo   ❌ SifenSetupCommand faltante
)

set /a total_checks+=1
if exist "app\Console\Commands\SifenTestCommand.php" (
    echo   ✅ SifenTestCommand
    set /a checks_passed+=1
) else (
    echo   ❌ SifenTestCommand faltante
)

:: Verificar configuraciones
echo.
echo ⚙️ Configuraciones:
set /a total_checks+=1
if exist "config\sifen.php" (
    echo   ✅ Configuracion SIFEN
    set /a checks_passed+=1
) else (
    echo   ❌ Configuracion SIFEN faltante
)

:: Calcular porcentaje
set /a percentage=checks_passed*100/total_checks

:: Mostrar resumen
echo.
echo ============================================
echo             📊 RESUMEN FINAL
echo ============================================
echo.
echo Verificaciones exitosas: %checks_passed%/%total_checks%
echo Porcentaje completado: %percentage%%%
echo.

if %percentage% geq 90 (
    echo 🎉 ¡EXCELENTE! Sistema SIFEN completamente funcional
    echo.
    echo 🚀 Proximos pasos recomendados:
    echo   1. php artisan sifen:company (configurar empresa)
    echo   2. php artisan sifen:test (probar conexiones)
    echo   3. Acceder al dashboard web
) else if %percentage% geq 70 (
    echo ⚠️ BUENO - Sistema mayormente funcional con algunas observaciones
    echo.
    echo 🔧 Revisar elementos marcados con ❌ arriba
) else (
    echo ❌ CRITICO - Sistema requiere atencion inmediata
    echo.
    echo 🆘 Ejecutar: instalar-sifen.bat para resolver problemas
)

echo.
echo 📚 Documentacion: MANUAL-INSTALACION.md
echo 🌐 Dashboard: http://localhost/sifen/dashboard
echo.
pause
