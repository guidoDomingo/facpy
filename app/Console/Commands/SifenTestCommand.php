<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SifenWebService;
use App\Services\SifenCertificateService;
use App\Models\Company;
use Illuminate\Support\Facades\File;

class SifenTestCommand extends Command
{
    protected $signature = 'sifen:test {--service=all : Servicio a probar (all, certificate, ping, ruc)}';
    protected $description = 'Probar conexión y servicios SIFEN';

    private $webService;
    private $certificateService;

    public function __construct()
    {
        parent::__construct();
        $this->webService = app(SifenWebService::class);
        $this->certificateService = app(SifenCertificateService::class);
    }

    public function handle()
    {
        $this->info('🧪 Probando Servicios SIFEN...');

        $service = $this->option('service');

        switch ($service) {
            case 'certificate':
                $this->testCertificate();
                break;
            case 'ping':
                $this->testPing();
                break;
            case 'ruc':
                $this->testRuc();
                break;
            case 'all':
            default:
                $this->testAll();
                break;
        }
    }

    private function testAll()
    {
        $this->testEnvironment();
        $this->testCertificate();
        $this->testPing();
        $this->testRuc();
        $this->showSummary();
    }

    private function testEnvironment()
    {
        $this->info('🌍 Verificando Entorno...');

        $company = Company::first();
        if (!$company) {
            $this->error('  ✗ No hay empresa configurada');
            return false;
        }

        $this->line("  ✓ Empresa: {$company->name}");
        $this->line("  ✓ RUC: {$company->ruc}-{$company->dv}");
        $this->line("  ✓ Ambiente: {$company->sifen_environment}");

        $url = $company->sifen_environment === 'production' 
            ? config('services.sifen.prod_url')
            : config('services.sifen.test_url');

        $this->line("  ✓ URL: {$url}");

        return true;
    }

    private function testCertificate()
    {
        $this->info('🔐 Probando Certificado...');

        try {
            $company = Company::first();
            if (!$company) {
                $this->error('  ✗ No hay empresa configurada');
                return false;
            }

            $certPath = storage_path($company->sifen_certificate_path);

            if (!File::exists($certPath)) {
                $this->error("  ✗ Certificado no encontrado: {$certPath}");
                return false;
            }

            $this->line("  ✓ Archivo certificado existe");

            $certData = $this->certificateService->getCertificateData(
                $certPath,
                $company->sifen_certificate_password
            );

            if ($certData) {
                $this->line("  ✓ Certificado válido");
                $this->line("  ✓ Serie: " . ($certData['serialNumber'] ?? 'N/A'));
                $this->line("  ✓ Válido hasta: " . ($certData['validTo'] ?? 'N/A'));
                return true;
            } else {
                $this->error("  ✗ No se pudo leer el certificado");
                return false;
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Error: " . $e->getMessage());
            return false;
        }
    }

    private function testPing()
    {
        $this->info('📡 Probando Conectividad...');

        try {
            $company = Company::first();
            if (!$company) {
                $this->error('  ✗ No hay empresa configurada');
                return false;
            }

            $url = $company->sifen_environment === 'production' 
                ? config('services.sifen.prod_url')
                : config('services.sifen.test_url');

            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->get($url . '?wsdl');

            if ($response->getStatusCode() === 200) {
                $this->line("  ✓ Conexión exitosa");
                $this->line("  ✓ Status: " . $response->getStatusCode());
                return true;
            } else {
                $this->error("  ✗ Status: " . $response->getStatusCode());
                return false;
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Error de conexión: " . $e->getMessage());
            return false;
        }
    }

    private function testRuc()
    {
        $this->info('🔍 Probando Consulta RUC...');

        try {
            $company = Company::first();
            if (!$company) {
                $this->error('  ✗ No hay empresa configurada');
                return false;
            }

            $response = $this->webService->consultarRUC($company->ruc . $company->dv);

            if ($response && isset($response['success']) && $response['success']) {
                $this->line("  ✓ Consulta RUC exitosa");
                $this->line("  ✓ RUC válido y activo");
                return true;
            } else {
                $this->warn("  ⚠ Respuesta inesperada de consulta RUC");
                $this->line("  📝 Respuesta: " . json_encode($response, JSON_PRETTY_PRINT));
                return false;
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Error en consulta: " . $e->getMessage());
            return false;
        }
    }

    private function showSummary()
    {
        $this->info('📊 Resumen de Pruebas');
        $this->line('');
        $this->line('Si todas las pruebas pasaron, el sistema está listo para:');
        $this->line('• Generar documentos electrónicos');
        $this->line('• Enviar a SIFEN para autorización');
        $this->line('• Generar KuDE y códigos QR');
        $this->line('• Procesar eventos y NCEs');
        $this->line('');
        $this->info('🚀 Sistema SIFEN operativo');
    }
}
