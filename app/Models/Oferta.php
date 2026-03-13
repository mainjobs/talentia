<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Oferta extends Model
{
    protected $fillable = ['titulo', 'descripcion', 'criterios_filtrado', 'sync_with_crm', 'external_platform_id', 'source_student_id', 'propietario_clientify', 'etiqueta_clientify'];

    protected $casts = [
        'sync_with_crm' => 'boolean',
        'etiqueta_clientify' => 'array',
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function externalPlatform()
    {
        return $this->belongsTo(ExternalPlatform::class, 'external_platform_id');
    }

    public function sourceStudent()
    {
        return $this->belongsTo(SourceStudent::class, 'source_student_id');
    }

    // App/Models/Oferta.php (o el modelo que corresponda)

    public function leadsAptos(): HasMany
    {
        return $this->leads()->where('apto', true);
    }

    public function leadsNoAptos(): HasMany
    {
        return $this->leads()->where('apto', false);
    }
}
