@echo off
echo Limpiando cache de Laravel...
cd c:\laragon\www\curso-greenter
php artisan config:clear
php artisan route:clear  
php artisan cache:clear
php artisan config:cache
echo Cache limpiado. Verificando rutas...
php artisan route:list --path=invoices
pause
