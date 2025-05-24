<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'plate',
        'model',
        'brand',
        'year',
        'km',
        'status',
        'licensing_due_date'
    ];

    protected $casts = [
        'km' => 'decimal:2',
        'licensing_due_date' => 'date',
        'year' => 'integer'
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(VehicleUsage::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'maintenance' => 'Manutenção',
            default => $this->status
        };
    }
}
