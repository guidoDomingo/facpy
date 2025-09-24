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
            // Campos específicos para Paraguay
            $table->string('nombre_fantasia')->nullable()->after('razon_social');
            $table->string('codigo_departamento', 2)->nullable()->after('cert_password');
            $table->string('departamento', 100)->nullable()->after('codigo_departamento');
            $table->string('codigo_distrito', 3)->nullable()->after('departamento');
            $table->string('distrito', 100)->nullable()->after('codigo_distrito');
            $table->string('codigo_ciudad', 5)->nullable()->after('distrito');
            $table->string('ciudad', 100)->nullable()->after('codigo_ciudad');
            $table->string('numero_casa', 50)->nullable()->after('ciudad');
            $table->string('punto_expedicion', 3)->default('001')->after('numero_casa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Eliminar campos específicos de Paraguay
            $table->dropColumn([
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
