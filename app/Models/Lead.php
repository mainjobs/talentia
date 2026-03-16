<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'oferta_id',
        'nombre',
        'email',
        'telefono',
        'datos_extraidos',
        'analisis_ia',
        'apto',
        'cv_path',
        'in_clientify',
        'synced_at',
        'estado',
    ];

    // Indica a Laravel que 'datos_extraidos' es un array/JSON
    protected $casts = [
        'datos_extraidos' => 'array',
        'apto' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function oferta()
    {
        return $this->belongsTo(Oferta::class);
    }
}
