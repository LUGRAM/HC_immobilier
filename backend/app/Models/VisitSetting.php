<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// ============================================
// MODEL: VisitSetting
// ============================================
class VisitSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_price', 'reminder_hours_before',
        'auto_reminders_enabled', 'available_time_slots'
    ];

    protected $casts = [
        'visit_price' => 'decimal:2',
        'reminder_hours_before' => 'integer',
        'auto_reminders_enabled' => 'boolean',
        'available_time_slots' => 'array',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([]);
    }
}
