<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ParaguayTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador para Paraguay
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Administrador Paraguay',
                'email' => 'admin@test.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Crear usuario demo
        $demo = User::firstOrCreate(
            ['email' => 'demo@paraguay.com'],
            [
                'name' => 'Usuario Demo Paraguay',
                'email' => 'demo@paraguay.com',
                'password' => Hash::make('demo123'),
                'email_verified_at' => now(),
            ]
        );

        // Crear empresas para Paraguay si no existen
        $empresa1 = Company::firstOrCreate(
            ['ruc' => '80123456-7'],
            [
                'user_id' => $admin->id,
                'ruc' => '80123456-7',
                'razon_social' => 'Empresa Demo Paraguay SRL',
                'nombre_comercial' => 'Demo Paraguay',
                'nombre_fantasia' => 'Demo PY',
                'direccion' => 'Av. Mariscal López 1234',
                'telefono' => '+595 21 123456',
                'email' => 'contacto@demopy.com',
                'departamento' => 'CAPITAL',
                'distrito' => 'ASUNCIÓN',
                'ciudad' => 'ASUNCIÓN',
                'codigo_departamento' => '01',
                'codigo_distrito' => '01',
                'punto_expedicion' => '001',
                'actividad_economica' => 'Servicios de consultoría',
                'regimen_tributario' => 'General',
                'estado' => 'activo',
                
                // Campos de compatibilidad con versión anterior
                'tipo_documento' => '6',
                'numero_documento' => '80123456-7',
                'codigo_pais' => 'PY',
                'ubigeo' => '010101',
                'urbanizacion' => 'Centro',
            ]
        );

        $empresa2 = Company::firstOrCreate(
            ['ruc' => '80987654-3'],
            [
                'user_id' => $demo->id,
                'ruc' => '80987654-3',
                'razon_social' => 'Comercial Paraguay SA',
                'nombre_comercial' => 'Comercial PY',
                'nombre_fantasia' => 'ComPY',
                'direccion' => 'Av. España 5678',
                'telefono' => '+595 21 987654',
                'email' => 'info@comercialpy.com',
                'departamento' => 'CENTRAL',
                'distrito' => 'SAN LORENZO',
                'ciudad' => 'SAN LORENZO',
                'codigo_departamento' => '02',
                'codigo_distrito' => '15',
                'punto_expedicion' => '001',
                'actividad_economica' => 'Comercio al por menor',
                'regimen_tributario' => 'General',
                'estado' => 'activo',
                
                // Campos de compatibilidad
                'tipo_documento' => '6',
                'numero_documento' => '80987654-3',
                'codigo_pais' => 'PY',
                'ubigeo' => '021501',
                'urbanizacion' => 'Centro',
            ]
        );

        $this->command->info('✅ Usuarios y empresas de Paraguay creados exitosamente:');
        $this->command->info("👤 Admin: admin@test.com / password123");
        $this->command->info("👤 Demo: demo@paraguay.com / demo123");
        $this->command->info("🏢 Empresa 1: {$empresa1->razon_social} (RUC: {$empresa1->ruc})");
        $this->command->info("🏢 Empresa 2: {$empresa2->razon_social} (RUC: {$empresa2->ruc})");
    }
}
