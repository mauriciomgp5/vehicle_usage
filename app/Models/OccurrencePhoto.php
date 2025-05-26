<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OccurrencePhoto extends Model
{
    protected $fillable = [
        'occurrence_id',
        'filename',
        'original_filename',
        'path',
        'mime_type',
        'size',
        'whatsapp_media_id',
        'caption'
    ];

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(Occurrence::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
