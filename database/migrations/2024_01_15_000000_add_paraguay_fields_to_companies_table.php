<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Eliminar campos de Perú (SUNAT) que ya no se usan
            $table->dropColumn(['sol_user', 'sol_pass', 'client_id', 'client_secret']);
            
            // Agregar campos específicos para Paraguay (SIFEN)
            $table->string('cert_password')->nullable()->after('cert_path');
            $table->string('nombre_fantasia')->nullable()->after('razon_social');
            $table->string('codigo_departamento', 2)->nullable()->after('direccion');
            $table->string('departamento')->nullable()->after('codigo_departamento');
            $table->string('codigo_distrito', 3)->nullable()->after('departamento');
            $table->string('distrito')->nullable()->after('codigo_distrito');
            $table->string('codigo_ciudad', 5)->nullable()->after('distrito');
            $table->string('ciudad')->nullable()->after('codigo_ciudad');
            $table->string('numero_casa')->nullable()->after('ciudad');
            $table->string('punto_expedicion', 3)->default('001')->after('numero_casa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Restaurar campos de Perú
            $table->string('sol_user')->nullable();
            $table->string('sol_pass')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            
            // Eliminar campos de Paraguay
            $table->dropColumn([
                'cert_password',
                'nombre_fantasia',
                'codigo_departamento',
                'departamento',
                'codigo_distrito',
                'distrito',
                'codigo_ciudad',
                'ciudad',
                'numero_casa',
                'punto_expedicion'
            ]);
        });
    }
};
