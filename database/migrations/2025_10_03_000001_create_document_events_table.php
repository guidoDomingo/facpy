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
        Schema::create('document_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electronic_document_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('event_type', 50)->comment('Tipo de evento');
            $table->string('event_code', 10)->comment('Código del evento SIFEN');
            $table->text('description')->comment('Descripción del evento');
            $table->longText('xml_content')->nullable()->comment('XML del evento firmado');
            $table->json('sifen_response')->nullable()->comment('Respuesta de SIFEN');
            $table->string('status', 20)->default('pendiente')->comment('Estado del evento');
            $table->json('error_details')->nullable()->comment('Detalles de errores');
            $table->timestamp('processed_at')->nullable()->comment('Fecha de procesamiento');
            $table->timestamps();
            
            // Índices
            $table->index(['electronic_document_id', 'event_type']);
            $table->index('status');
            $table->index('event_type');
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_events');
    }
};
