<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

echo "🇵🇾 Creando usuarios para Paraguay SIFEN...\n";

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

    echo "✅ Usuario admin creado: {$admin->email}\n";

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

    echo "✅ Empresa creada: {$company->razon_social} (RUC: {$company->ruc})\n";
    echo "\n🎯 Credenciales de prueba:\n";
    echo "📧 Email: admin@test.com\n";
    echo "🔐 Password: password123\n";
    echo "🏢 Company ID: {$company->id}\n";
    echo "\n🚀 Ahora puedes hacer login en: http://127.0.0.1:8000/api/login\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
