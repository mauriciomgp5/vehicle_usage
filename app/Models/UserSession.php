<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $fillable = [
        'phone',
        'current_menu',
        'last_interaction',
        'session_data'
    ];

    protected $casts = [
        'session_data' => 'array',
        'last_interaction' => 'datetime'
    ];
} 