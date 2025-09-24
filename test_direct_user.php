<?php

// Script simple para probar creación de usuario
require_once __DIR__ . '/vendor/autoload.php';

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

// Boot the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Crear el usuario usando Eloquent directamente
try {
    echo "Probando creación directa de usuario...\n";
    
    $user = \App\Models\User::create([
        'name' => 'Test User Direct',
        'email' => 'test-direct@example.com', 
        'password' => \Illuminate\Support\Facades\Hash::make('password123')
    ]);
    
    echo "Usuario creado exitosamente!\n";
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
