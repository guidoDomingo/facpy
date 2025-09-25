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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
