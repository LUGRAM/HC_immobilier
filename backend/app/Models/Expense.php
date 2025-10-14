<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;


// ============================================
// MODEL: Expense
// ============================================
class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'category', 'description',
        'amount', 'expense_date', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeInMonth($query, int $year, int $month)
    {
        return $query->whereYear('expense_date', $year)
                     ->whereMonth('expense_date', $month);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }
}

