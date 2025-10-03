<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'razon_social',
        'ruc',
        'direccion',
        'logo_path',
        'cert_path',
        'cert_password',
        'production',
        'user_id',
        // Campos específicos para Paraguay (SIFEN)
        'nombre_fantasia',
        'codigo_departamento',
        'departamento',
        'codigo_distrito',
        'distrito',
        'codigo_ciudad',
        'ciudad',
        'numero_casa',
        'punto_expedicion',
        'timbrado',
        'cert_valid_from',
        'cert_valid_to',
        'business_name'
    ];

    protected $casts = [
        'production' => 'boolean',
        'cert_valid_from' => 'datetime',
        'cert_valid_to' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con documentos electrónicos
     */
    public function electronicDocuments()
    {
        return $this->hasMany(ElectronicDocument::class);
    }

    /**
     * Verifica si el certificado es válido
     */
    public function hasCertificateValid(): bool
    {
        return $this->cert_path && 
               file_exists($this->cert_path) && 
               $this->cert_valid_to && 
               $this->cert_valid_to->isFuture();
    }

    /**
     * Obtiene el nombre comercial (business_name o razon_social)
     */
    public function getBusinessNameAttribute($value)
    {
        return $value ?? $this->razon_social;
    }
}
