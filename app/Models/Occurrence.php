<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Occurrence extends Model
{
    protected $fillable = [
        'vehicle_usage_id',
        'description',
        'type',
        'severity',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    public function vehicleUsage(): BelongsTo
    {
        return $this->belongsTo(VehicleUsage::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OccurrencePhoto::class);
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'incident' => 'Incidente',
            'maintenance' => 'Manutenção',
            'damage' => 'Dano',
            'other' => 'Outros',
            default => $this->type
        };
    }

    public function getSeverityNameAttribute(): string
    {
        return match($this->severity) {
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'critical' => 'Crítica',
            default => $this->severity
        };
    }
}
