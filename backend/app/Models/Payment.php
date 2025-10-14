<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{

    protected $fillable = [
        'transaction_id',
        'user_id',
        'payable_type',
        'payable_id',
        'amount',
        'type',
        'method',
        'status',
        'provider',
        'provider_transaction_id',
        'operator',
        'provider_response',
        'phone_number',
        'completed_at',
        'due_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_response' => 'array',
        'metadata' => 'array',
        'completed_at' => 'datetime',
        'due_date' => 'date',
    ];

    // ============================================
    // CONSTANTES
    // ============================================
    
    public const TYPE_VISIT = 'visit';
    public const TYPE_RENT = 'rent';
    public const TYPE_WATER = 'water';
    public const TYPE_ELECTRICITY = 'electricity';
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_OTHER = 'other';

    public const METHOD_MOBILE_MONEY = 'mobile_money';
    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CARD = 'card';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    // ============================================
    // RELATIONS
    // ============================================

    /**
     * Relation polymorphique - peut être lié à Invoice, Appointment, etc.
     */
    public function payable()
    {
        return $this->morphTo();
    }

    /**
     * Utilisateur qui a effectué le paiement
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('completed_at', now()->month)
                    ->whereYear('completed_at', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('completed_at', now()->year);
    }

    // ============================================
    // ACCESSORS & MUTATORS
    // ============================================

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'visit' => 'Visite',
            'rent' => 'Loyer',
            'water' => 'Eau',
            'electricity' => 'Électricité',
            'deposit' => 'Caution',
            'other' => 'Autre',
        ];

        return $labels[$this->type] ?? 'Inconnu';
    }

    public function getMethodLabelAttribute(): string
    {
        $labels = [
            'mobile_money' => 'Mobile Money',
            'cash' => 'Espèces',
            'bank_transfer' => 'Virement bancaire',
            'card' => 'Carte bancaire',
        ];

        return $labels[$this->method] ?? 'Inconnu';
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Complété',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
            'cancelled' => 'Annulé',
        ];

        return $labels[$this->status] ?? 'Inconnu';
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'orange',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'refunded' => 'purple',
            'cancelled' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 0, ',', ' ') . ' FCFA';
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason,
        ]);
    }

    public function refund(): void
    {
        $this->update(['status' => self::STATUS_REFUNDED]);
    }

    /**
     * Générer un ID de transaction unique
     */
    public static function generateTransactionId(): string
    {
        return 'TXN-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}