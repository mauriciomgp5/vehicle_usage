<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
