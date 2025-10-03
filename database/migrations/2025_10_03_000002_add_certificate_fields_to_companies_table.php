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
            // Campos de certificado
            $table->timestamp('cert_valid_from')->nullable()->after('cert_password');
            $table->timestamp('cert_valid_to')->nullable()->after('cert_valid_from');
            
            // Campos adicionales SIFEN
            $table->string('business_name')->nullable()->after('razon_social');
            $table->string('timbrado', 50)->nullable()->after('punto_expedicion');
            
            // Campos para KuDE y archivos
            $table->string('kude_path')->nullable()->after('cert_valid_to');
            $table->string('qr_path')->nullable()->after('kude_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'cert_valid_from',
                'cert_valid_to',
                'business_name',
                'timbrado',
                'kude_path',
                'qr_path'
            ]);
        });
    }
};
