<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_platform_id',
        'name',
        'description',
        'short_description',
        'tag',
        'active',
        'token',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function externalPlatform(): BelongsTo
    {
        return $this->belongsTo(ExternalPlatform::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    
}
