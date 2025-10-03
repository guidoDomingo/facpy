<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;

class SifenCompanyCommand extends Command
{
    protected $signature = 'sifen:company {--update : Actualizar empresa existente}';
    protected $description = 'Configurar o actualizar empresa para SIFEN';

    public function handle()
    {
        $this->info('🏢 Configuración de Empresa SIFEN');

        if ($this->option('update')) {
            $this->updateCompany();
        } else {
            $this->createOrUpdateCompany();
        }
    }

    private function createOrUpdateCompany()
    {
        $company = Company::first();

        if ($company && !$this->confirm('¿Actualizar empresa existente?')) {
            return;
        }

        $data = $this->gatherCompanyData();

        if ($company) {
            $company->update($data);
            $this->info('✅ Empresa actualizada exitosamente');
        } else {
            Company::create($data);
            $this->info('✅ Empresa creada exitosamente');
        }

        $this->showCompanyInfo(Company::first());
    }

    private function updateCompany()
    {
        $company = Company::first();

        if (!$company) {
            $this->error('❌ No existe empresa configurada');
            return;
        }

        $this->info('Empresa actual:');
        $this->showCompanyInfo($company);

        if ($this->confirm('¿Continuar con actualización?')) {
            $data = $this->gatherCompanyData($company);
            $company->update($data);
            $this->info('✅ Empresa actualizada exitosamente');
            $this->showCompanyInfo($company->fresh());
        }
    }

    private function gatherCompanyData($company = null)
    {
        return [
            'ruc' => $this->ask('RUC', $company->ruc ?? null),
            'dv' => $this->ask('Dígito Verificador', $company->dv ?? null),
            'name' => $this->ask('Razón Social', $company->name ?? null),
            'fantasy_name' => $this->ask('Nombre Fantasía (opcional)', $company->fantasy_name ?? null),
            'activity_code' => $this->ask('Código de Actividad Económica', $company->activity_code ?? null),
            'address' => $this->ask('Dirección', $company->address ?? null),
            'phone' => $this->ask('Teléfono (opcional)', $company->phone ?? null),
            'email' => $this->ask('Email (opcional)', $company->email ?? null),
            'department_code' => $this->ask('Código Departamento (ej: 11)', $company->department_code ?? '11'),
            'district_code' => $this->ask('Código Distrito (ej: 1)', $company->district_code ?? '1'),
            'city_code' => $this->ask('Código Ciudad (ej: 1)', $company->city_code ?? '1'),
            'establishment_code' => $this->ask('Código Establecimiento (001)', $company->establishment_code ?? '001'),
            'point_of_sale_code' => $this->ask('Código Punto de Venta (001)', $company->point_of_sale_code ?? '001'),
            'sifen_certificate_path' => $this->ask('Ruta Certificado P12', $company->sifen_certificate_path ?? 'storage/certificates/certificate.p12'),
            'sifen_certificate_password' => $this->secret('Password del Certificado'),
            'sifen_environment' => $this->choice('Ambiente SIFEN', ['test', 'production'], $company->sifen_environment ?? 'test'),
        ];
    }

    private function showCompanyInfo($company)
    {
        $this->table(['Campo', 'Valor'], [
            ['RUC', $company->ruc . '-' . $company->dv],
            ['Razón Social', $company->name],
            ['Nombre Fantasía', $company->fantasy_name ?? 'N/A'],
            ['Actividad Económica', $company->activity_code],
            ['Dirección', $company->address],
            ['Teléfono', $company->phone ?? 'N/A'],
            ['Email', $company->email ?? 'N/A'],
            ['Establecimiento', $company->establishment_code],
            ['Punto de Venta', $company->point_of_sale_code],
            ['Ambiente SIFEN', $company->sifen_environment],
            ['Certificado', $company->sifen_certificate_path],
        ]);
    }
}
