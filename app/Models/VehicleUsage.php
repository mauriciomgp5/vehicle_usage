<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleUsage extends Model
{
    protected $fillable = [
        'vehicle_id',
        'user_id',
        'checkout_at',
        'checkin_at',
        'initial_km',
        'final_km',
        'purpose',
        'notes'
    ];

    protected $casts = [
        'checkout_at' => 'datetime',
        'checkin_at' => 'datetime',
        'initial_km' => 'decimal:2',
        'final_km' => 'decimal:2'
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class);
    }
}
