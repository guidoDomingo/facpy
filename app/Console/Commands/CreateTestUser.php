<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea usuarios de prueba para Paraguay SIFEN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🇵🇾 Creando usuarios para Paraguay SIFEN...');

        try {
            // Crear usuario administrador
            $admin = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name' => 'Admin Paraguay SIFEN',
                    'email' => 'admin@test.com',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );

            $this->info("✅ Usuario admin creado: {$admin->email}");

            // Crear empresa asociada si no existe
            $company = Company::firstOrCreate(
                ['ruc' => '80123456-7'],
                [
                    'user_id' => $admin->id,
                    'ruc' => '80123456-7',
                    'razon_social' => 'Empresa Demo Paraguay SRL',
                    'nombre_comercial' => 'Demo Paraguay',
                    'direccion' => 'Av. Mariscal López 1234',
                    'telefono' => '+595 21 123456',
                    'email' => 'contacto@demopy.com',
                    'departamento' => 'CAPITAL',
                    'distrito' => 'ASUNCIÓN',
                    'ciudad' => 'ASUNCIÓN',
                    'codigo_departamento' => '01',
                    'punto_expedicion' => '001',
                    'actividad_economica' => 'Servicios de consultoría',
                    'estado' => 'activo',
                ]
            );

            $this->info("✅ Empresa creada: {$company->razon_social} (RUC: {$company->ruc})");
            $this->info('');
            $this->info('🎯 Credenciales de prueba:');
            $this->info('📧 Email: admin@test.com');
            $this->info('🔐 Password: password123');
            $this->info("🏢 Company ID: {$company->id}");
            $this->info('');
            $this->info('🚀 Ahora puedes hacer login en: http://127.0.0.1:8000/api/login');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
