<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class ParaguaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario de prueba
        $user = User::firstOrCreate(
            ['email' => 'admin@paraguay.com'],
            [
                'name' => 'Admin Paraguay',
                'password' => Hash::make('password123'),
            ]
        );

        // Crear empresa de prueba para Paraguay
        Company::firstOrCreate(
            ['ruc' => '80123456-7'],
            [
                'razon_social' => 'Empresa Demo Paraguay SRL',
                'nombre_fantasia' => 'Demo Paraguay',
                'direccion' => 'Av. Eusebio Ayala 1234',
                'codigo_departamento' => '11',
                'departamento' => 'CAPITAL',
                'codigo_distrito' => '1',
                'distrito' => 'ASUNCIÓN',
                'codigo_ciudad' => '1',
                'ciudad' => 'ASUNCIÓN',
                'numero_casa' => '1234',
                'punto_expedicion' => '001',
                'production' => false,
                'user_id' => $user->id,
                'cert_path' => '',
            ]
        );

        // Crear otra empresa de prueba
        Company::firstOrCreate(
            ['ruc' => '80987654-3'],
            [
                'razon_social' => 'Comercial Paraguay SA',
                'nombre_fantasia' => 'Comercial PY',
                'direccion' => 'Mcal. López 567',
                'codigo_departamento' => '11',
                'departamento' => 'CAPITAL',
                'codigo_distrito' => '1',
                'distrito' => 'ASUNCIÓN',
                'codigo_ciudad' => '1',
                'ciudad' => 'ASUNCIÓN',
                'numero_casa' => '567',
                'punto_expedicion' => '001',
                'production' => false,
                'user_id' => $user->id,
                'cert_path' => '',
            ]
        );

        $this->command->info('Datos de prueba para Paraguay creados exitosamente.');
    }
}
