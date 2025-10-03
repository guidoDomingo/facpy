<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class SifenSetupCommand extends Command
{
    protected $signature = 'sifen:setup {--force : Sobrescribir configuraciones existentes}';
    protected $description = 'Configurar el sistema SIFEN Paraguay inicial';

    public function handle()
    {
        $this->info('🇵🇾 Configurando Sistema SIFEN Paraguay...');

        // Verificar directorios
        $this->checkDirectories();

        // Verificar dependencias
        $this->checkDependencies();

        // Configurar certificados
        $this->setupCertificates();

        // Verificar base de datos
        $this->checkDatabase();

        // Verificar configuraciones
        $this->checkConfiguration();

        $this->info('✅ Configuración SIFEN completada exitosamente');
        $this->showNextSteps();
    }

    private function checkDirectories()
    {
        $this->info('📁 Verificando directorios...');

        $directories = [
            storage_path('certificates'),
            storage_path('sifen'),
            storage_path('sifen/xml'),
            storage_path('sifen/pdf'),
            storage_path('sifen/logs'),
            storage_path('sifen/temp'),
        ];

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->line("  ✓ Creado: {$dir}");
            } else {
                $this->line("  ✓ Existe: {$dir}");
            }
        }
    }

    private function checkDependencies()
    {
        $this->info('📦 Verificando dependencias...');

        $required = [
            'guzzlehttp/guzzle' => 'Cliente HTTP para web services',
            'endroid/qr-code' => 'Generación de códigos QR',
            'tecnickcom/tcpdf' => 'Generación de PDFs',
            'tymon/jwt-auth' => 'Autenticación JWT para API',
        ];

        $composerLock = json_decode(File::get(base_path('composer.lock')), true);
        $installed = collect($composerLock['packages'])->pluck('name')->toArray();

        foreach ($required as $package => $description) {
            if (in_array($package, $installed)) {
                $this->line("  ✓ {$package} - {$description}");
            } else {
                $this->error("  ✗ Falta: {$package} - {$description}");
            }
        }
    }

    private function setupCertificates()
    {
        $this->info('🔐 Configurando certificados...');

        $certDir = storage_path('certificates');
        $testCert = $certDir . '/test-certificate.p12';

        if (!File::exists($testCert)) {
            $this->warn('  ⚠ No se encontró certificado de prueba');
            $this->line('  📝 Coloque su certificado .p12 en: ' . $certDir);
        } else {
            $this->line('  ✓ Certificado de prueba encontrado');
        }

        // Verificar permisos
        if (!is_writable($certDir)) {
            $this->error('  ✗ El directorio de certificados no tiene permisos de escritura');
        } else {
            $this->line('  ✓ Permisos de certificados correctos');
        }
    }

    private function checkDatabase()
    {
        $this->info('🗄️ Verificando base de datos...');

        try {
            $connection = Config::get('database.default');
            $this->line("  ✓ Conexión: {$connection}");

            // Verificar tablas SIFEN
            $tables = [
                'electronic_documents',
                'document_events',
                'companies'
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $this->line("  ✓ Tabla: {$table}");
                } else {
                    $this->error("  ✗ Falta tabla: {$table}");
                }
            }

        } catch (\Exception $e) {
            $this->error('  ✗ Error de conexión: ' . $e->getMessage());
        }
    }

    private function checkConfiguration()
    {
        $this->info('⚙️ Verificando configuraciones...');

        $required = [
            'SIFEN_TEST_URL',
            'SIFEN_PROD_URL',
            'SIFEN_DEFAULT_ENV',
            'JWT_SECRET'
        ];

        foreach ($required as $config) {
            $value = env($config);
            if ($value) {
                $this->line("  ✓ {$config}");
            } else {
                $this->error("  ✗ Falta: {$config}");
            }
        }
    }

    private function showNextSteps()
    {
        $this->info('🚀 Próximos pasos:');
        $this->line('');
        $this->line('1. Configurar certificados P12 en storage/certificates/');
        $this->line('2. Ejecutar: php artisan migrate (si no está hecho)');
        $this->line('3. Configurar empresa: php artisan sifen:company');
        $this->line('4. Probar conexión: php artisan sifen:test');
        $this->line('5. Ver dashboard: /sifen/dashboard');
        $this->line('');
        $this->info('📚 Documentación completa en: IMPLEMENTACION-SIFEN.md');
    }
}
