<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DocumentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'electronic_document_id',
        'event_type',
        'event_code',
        'description',
        'xml_content',
        'sifen_response',
        'status',
        'error_details',
        'processed_at'
    ];

    protected $casts = [
        'sifen_response' => 'array',
        'error_details' => 'array',
        'processed_at' => 'datetime'
    ];

    /**
     * Tipos de eventos SIFEN
     */
    const TYPE_CANCELACION = 'cancelacion';
    const TYPE_INUTILIZACION = 'inutilizacion';
    const TYPE_DEVOLUCION = 'devolucion';
    const TYPE_AJUSTE = 'ajuste';
    const TYPE_NOTIFICACION_RECEPTOR = 'notificacion_receptor';
    const TYPE_CONFORMIDAD = 'conformidad';
    const TYPE_DISCONFORMIDAD = 'disconformidad';

    /**
     * Códigos de eventos SIFEN
     */
    const CODE_CANCELACION = '690';
    const CODE_INUTILIZACION = '691';
    const CODE_DEVOLUCION = '692';
    const CODE_AJUSTE = '693';
    const CODE_NOTIFICACION = '694';
    const CODE_CONFORMIDAD = '695';
    const CODE_DISCONFORMIDAD = '696';

    /**
     * Estados del evento
     */
    const STATUS_PENDING = 'pendiente';
    const STATUS_SENT = 'enviado';
    const STATUS_APPROVED = 'aprobado';
    const STATUS_REJECTED = 'rechazado';
    const STATUS_ERROR = 'error';

    /**
     * Relación con ElectronicDocument
     */
    public function electronicDocument(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class);
    }

    /**
     * Scope para eventos por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope para eventos pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para eventos de cancelación
     */
    public function scopeCancellations($query)
    {
        return $query->where('event_type', self::TYPE_CANCELACION);
    }

    /**
     * Verifica si es evento de cancelación
     */
    public function isCancellation(): bool
    {
        return $this->event_type === self::TYPE_CANCELACION;
    }

    /**
     * Verifica si es evento de inutilización
     */
    public function isInutilization(): bool
    {
        return $this->event_type === self::TYPE_INUTILIZACION;
    }

    /**
     * Obtiene el nombre del tipo de evento
     */
    public function getEventTypeNameAttribute(): string
    {
        $types = [
            self::TYPE_CANCELACION => 'Cancelación',
            self::TYPE_INUTILIZACION => 'Inutilización',
            self::TYPE_DEVOLUCION => 'Devolución',
            self::TYPE_AJUSTE => 'Ajuste',
            self::TYPE_NOTIFICACION_RECEPTOR => 'Notificación Receptor',
            self::TYPE_CONFORMIDAD => 'Conformidad',
            self::TYPE_DISCONFORMIDAD => 'Disconformidad'
        ];

        return $types[$this->event_type] ?? 'Desconocido';
    }

    /**
     * Obtiene el estado formateado
     */
    public function getStatusFormattedAttribute(): string
    {
        $states = [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_SENT => 'Enviado',
            self::STATUS_APPROVED => 'Aprobado',
            self::STATUS_REJECTED => 'Rechazado',
            self::STATUS_ERROR => 'Error'
        ];

        return $states[$this->status] ?? 'Desconocido';
    }

    /**
     * Actualiza el estado del evento
     */
    public function updateStatus($newStatus, $response = null, $errorDetails = null)
    {
        $this->update([
            'status' => $newStatus,
            'sifen_response' => $response,
            'error_details' => $errorDetails,
            'processed_at' => Carbon::now()
        ]);

        return $this;
    }

    /**
     * Crea evento de cancelación
     */
    public static function createCancellation(ElectronicDocument $document, $reason, $xmlContent)
    {
        return static::create([
            'electronic_document_id' => $document->id,
            'event_type' => self::TYPE_CANCELACION,
            'event_code' => self::CODE_CANCELACION,
            'description' => $reason,
            'xml_content' => $xmlContent,
            'status' => self::STATUS_PENDING
        ]);
    }

    /**
     * Crea evento de inutilización
     */
    public static function createInutilization($companyId, $serie, $rangeStart, $rangeEnd, $reason, $xmlContent)
    {
        return static::create([
            'electronic_document_id' => null, // No asociado a documento específico
            'event_type' => self::TYPE_INUTILIZACION,
            'event_code' => self::CODE_INUTILIZACION,
            'description' => "Inutilización Serie: {$serie}, Rango: {$rangeStart}-{$rangeEnd}, Motivo: {$reason}",
            'xml_content' => $xmlContent,
            'status' => self::STATUS_PENDING
        ]);
    }

    /**
     * Obtiene eventos pendientes de envío
     */
    public static function pendingToSend()
    {
        return static::where('status', self::STATUS_PENDING)
            ->whereNotNull('xml_content')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Obtiene estadísticas de eventos por tipo
     */
    public static function getEventStats($documentId = null)
    {
        $query = static::query();
        
        if ($documentId) {
            $query->where('electronic_document_id', $documentId);
        }

        return $query->selectRaw('event_type, status, COUNT(*) as count')
            ->groupBy(['event_type', 'status'])
            ->get()
            ->groupBy('event_type')
            ->map(function ($events) {
                return $events->pluck('count', 'status');
            })
            ->toArray();
    }
}
