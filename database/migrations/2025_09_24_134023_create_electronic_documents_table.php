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
        Schema::create('electronic_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('cdc', 44)->unique()->comment('Código de Control del Documento');
            $table->string('tipo_documento', 2)->comment('Tipo de documento SIFEN');
            $table->string('serie', 10);
            $table->string('numero_documento', 20);
            $table->date('fecha_emision');
            $table->string('receptor_ruc', 20)->nullable();
            $table->string('receptor_razon_social')->nullable();
            $table->decimal('total_documento', 15, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->longText('xml_content')->nullable();
            $table->longText('response_sifen')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('cdc');
            $table->index(['company_id', 'fecha_emision']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electronic_documents');
    }
};
