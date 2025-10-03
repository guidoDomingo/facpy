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
        Schema::table('electronic_documents', function (Blueprint $table) {
            // Campos para KuDE y QR
            $table->string('kude_path')->nullable()->after('response_sifen');
            $table->string('qr_path')->nullable()->after('kude_path');
            
            // Protocolo SIFEN
            $table->string('protocol_sifen')->nullable()->after('qr_path');
            
            // Historial de eventos
            $table->json('event_history')->nullable()->after('protocol_sifen');
            
            // Campos adicionales
            $table->string('motivo_nce')->nullable()->after('event_history')->comment('Motivo para NCE');
            $table->string('documento_asociado', 44)->nullable()->after('motivo_nce')->comment('CDC del documento asociado para NCE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('electronic_documents', function (Blueprint $table) {
            $table->dropColumn([
                'kude_path',
                'qr_path',
                'protocol_sifen',
                'event_history',
                'motivo_nce',
                'documento_asociado'
            ]);
        });
    }
};
