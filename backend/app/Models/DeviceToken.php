<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


// ============================================
// MODEL: DeviceToken
// ============================================
class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'token', 'platform', 'device_name', 'last_used_at'
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
