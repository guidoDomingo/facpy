<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ElectronicDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'cdc',
        'tipo_documento',
        'serie',
        'numero_documento',
        'fecha_emision',
        'receptor_ruc',
        'receptor_razon_social',
        'total_documento',
        'estado',
        'xml_content',
        'response_sifen',
        'kude_path',
        'qr_path',
        'protocol_sifen',
        'event_history'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'total_documento' => 'decimal:2',
        'response_sifen' => 'array',
        'event_history' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Estados posibles del documento
     */
    const STATUS_PENDING = 'pendiente';
    const STATUS_GENERATED = 'generado';
    const STATUS_SIGNED = 'firmado';
    const STATUS_SENT = 'enviado';
    const STATUS_APPROVED = 'aprobado';
    const STATUS_REJECTED = 'rechazado';
    const STATUS_CANCELLED = 'cancelado';
    const STATUS_ERROR = 'error';

    /**
     * Tipos de documento SIFEN
     */
    const TYPE_FACTURA = '01';
    const TYPE_AUTOFACTURA = '04';
    const TYPE_NOTA_CREDITO = '05';
    const TYPE_NOTA_DEBITO = '06';
    const TYPE_NOTA_REMISION = '07';

    /**
     * Relación con Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Eventos del documento
     */
    public function events()
    {
        return $this->hasMany(DocumentEvent::class);
    }

    /**
     * Scope para documentos por estado
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('estado', $status);
    }

    /**
     * Scope para documentos por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('tipo_documento', $type);
    }

    /**
     * Scope para documentos en rango de fechas
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('fecha_emision', [$startDate, $endDate]);
    }

    /**
     * Scope para documentos de hoy
     */
    public function scopeToday($query)
    {
        return $query->whereDate('fecha_emision', Carbon::today());
    }

    /**
     * Verifica si el documento puede ser cancelado (ventana 48h)
     */
    public function canBeCancelled(): bool
    {
        if ($this->estado !== self::STATUS_APPROVED) {
            return false;
        }

        $hoursSinceEmission = Carbon::parse($this->fecha_emision)->diffInHours(Carbon::now());
        return $hoursSinceEmission <= 48;
    }

    /**
     * Verifica si el documento es una NCE
     */
    public function isNotaCredito(): bool
    {
        return $this->tipo_documento === self::TYPE_NOTA_CREDITO;
    }

    /**
     * Obtiene el nombre del tipo de documento
     */
    public function getTipoDocumentoNameAttribute(): string
    {
        $types = [
            self::TYPE_FACTURA => 'Factura Electrónica',
            self::TYPE_AUTOFACTURA => 'Autofactura Electrónica',
            self::TYPE_NOTA_CREDITO => 'Nota de Crédito Electrónica',
            self::TYPE_NOTA_DEBITO => 'Nota de Débito Electrónica',
            self::TYPE_NOTA_REMISION => 'Nota de Remisión Electrónica'
        ];

        return $types[$this->tipo_documento] ?? 'Desconocido';
    }

    /**
     * Obtiene el estado formateado
     */
    public function getEstadoFormattedAttribute(): string
    {
        $states = [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_GENERATED => 'Generado',
            self::STATUS_SIGNED => 'Firmado',
            self::STATUS_SENT => 'Enviado',
            self::STATUS_APPROVED => 'Aprobado',
            self::STATUS_REJECTED => 'Rechazado',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_ERROR => 'Error'
        ];

        return $states[$this->estado] ?? 'Desconocido';
    }

    /**
     * Obtiene la clase CSS del estado
     */
    public function getEstadoCssClassAttribute(): string
    {
        $classes = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_GENERATED => 'info',
            self::STATUS_SIGNED => 'primary',
            self::STATUS_SENT => 'secondary',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_CANCELLED => 'dark',
            self::STATUS_ERROR => 'danger'
        ];

        return $classes[$this->estado] ?? 'secondary';
    }

    /**
     * Actualiza el estado del documento
     */
    public function updateStatus($newStatus, $details = null)
    {
        $oldStatus = $this->estado;
        
        $this->update([
            'estado' => $newStatus,
            'event_history' => array_merge($this->event_history ?? [], [
                [
                    'timestamp' => Carbon::now()->toISOString(),
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'details' => $details
                ]
            ])
        ]);

        return $this;
    }

    /**
     * Registra respuesta de SIFEN
     */
    public function recordSifenResponse($response)
    {
        $this->update([
            'response_sifen' => $response,
            'protocol_sifen' => $response['protocol'] ?? null
        ]);

        // Actualizar estado según respuesta
        if (isset($response['success']) && $response['success']) {
            $this->updateStatus(self::STATUS_APPROVED, 'Aprobado por SIFEN');
        } else {
            $this->updateStatus(self::STATUS_REJECTED, $response['message'] ?? 'Rechazado por SIFEN');
        }

        return $this;
    }

    /**
     * Obtiene documentos que requieren envío
     */
    public static function pendingToSend()
    {
        return static::whereIn('estado', [
            self::STATUS_GENERATED,
            self::STATUS_SIGNED
        ])->get();
    }

    /**
     * Obtiene estadísticas por estado
     */
    public static function getStatusStats($companyId = null)
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->selectRaw('estado, COUNT(*) as count')
            ->groupBy('estado')
            ->pluck('count', 'estado')
            ->toArray();
    }

    /**
     * Busca documentos por CDC
     */
    public static function findByCDC($cdc)
    {
        return static::where('cdc', $cdc)->first();
    }

    /**
     * Genera número correlativo para la empresa
     */
    public static function getNextNumber(Company $company, $tipoDocumento, $serie)
    {
        $lastDoc = static::where('company_id', $company->id)
            ->where('tipo_documento', $tipoDocumento)
            ->where('serie', $serie)
            ->orderBy('numero_documento', 'desc')
            ->first();

        if (!$lastDoc) {
            return '1';
        }

        return (string)((int)$lastDoc->numero_documento + 1);
    }

    /**
     * Verifica si un número ya existe
     */
    public static function numberExists(Company $company, $tipoDocumento, $serie, $numero)
    {
        return static::where('company_id', $company->id)
            ->where('tipo_documento', $tipoDocumento)
            ->where('serie', $serie)
            ->where('numero_documento', $numero)
            ->exists();
    }
}
