<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalPlatform extends Model
{
    protected $fillable = ['name', 'type', 'url', 'description', 'active'];

    public function ofertas()
    {
        return $this->hasMany(Oferta::class, 'external_platform_id');
    }
}
