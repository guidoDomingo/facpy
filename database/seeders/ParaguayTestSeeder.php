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
                'nombre_fantasia' => 'Demo PY',
                'direccion' => 'Av. Mariscal López 1234',
                'departamento' => 'CAPITAL',
                'distrito' => 'ASUNCIÓN',
                'ciudad' => 'ASUNCIÓN',
                'codigo_departamento' => '01',
                'codigo_distrito' => '01',
                'numero_casa' => '1234',
                'punto_expedicion' => '001',
                'cert_path' => '',
                'production' => false,
            ]
        );

        $empresa2 = Company::firstOrCreate(
            ['ruc' => '80987654-3'],
            [
                'user_id' => $demo->id,
                'ruc' => '80987654-3',
                'razon_social' => 'Comercial Paraguay SA',
                'nombre_fantasia' => 'ComPY',
                'direccion' => 'Av. España 5678',
                'departamento' => 'CENTRAL',
                'distrito' => 'SAN LORENZO',
                'ciudad' => 'SAN LORENZO',
                'codigo_departamento' => '02',
                'codigo_distrito' => '15',
                'numero_casa' => '5678',
                'punto_expedicion' => '001',
                'cert_path' => '',
                'production' => false,
            ]
        );

        $this->command->info('✅ Usuarios y empresas de Paraguay creados exitosamente:');
        $this->command->info("👤 Admin: admin@test.com / password123");
        $this->command->info("👤 Demo: demo@paraguay.com / demo123");
        $this->command->info("🏢 Empresa 1: {$empresa1->razon_social} (RUC: {$empresa1->ruc})");
        $this->command->info("🏢 Empresa 2: {$empresa2->razon_social} (RUC: {$empresa2->ruc})");
    }
}
