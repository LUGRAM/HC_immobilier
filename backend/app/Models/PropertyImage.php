<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

    // ============================================
// MODEL: PropertyImage
// ============================================
class PropertyImage extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'image_path', 'order', 'is_primary'];

    protected $casts = [
        'is_primary' => 'boolean',
        'order' => 'integer',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }
}


