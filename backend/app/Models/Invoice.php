<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'lease_id',
        'tenant_id',
        'type',
        'description',
        'amount',
        'due_date',
        'period_start',
        'period_end',
        'status',
        'paid_at',
        'amount_paid',
        'payment_reference',
        'payment_method',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    // ============================================
    // CONSTANTES
    // ============================================
    
    public const TYPE_RENT = 'rent';
    public const TYPE_WATER = 'water';
    public const TYPE_ELECTRICITY = 'electricity';
    public const TYPE_OTHER = 'other';

    public const STATUS_PENDING = 'pending';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_CANCELLED = 'cancelled';

    // ============================================
    // RELATIONS
    // ============================================

    /**
     * Bail lié à cette facture
     */
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Locataire de la facture
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /**
     * Paiements liés à cette facture (relation polymorphique)
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
                     ->orWhere(function($q) {
                         $q->where('status', self::STATUS_PENDING)
                           ->where('due_date', '<', now());
                     });
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForLandlord($query, int $landlordId)
    {
        return $query->whereHas('lease', function($q) use ($landlordId) {
            $q->where('landlord_id', $landlordId);
        });
    }

    public function scopeDueThisMonth($query)
    {
        return $query->whereMonth('due_date', now()->month)
                     ->whereYear('due_date', now()->year);
    }

    // ============================================
    // ACCESSORS & MUTATORS
    // ============================================

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'rent' => 'Loyer',
            'water' => 'Eau',
            'electricity' => 'Électricité',
            'other' => 'Autre',
        ];

        return $labels[$this->type] ?? 'Inconnu';
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'En attente',
            'overdue' => 'En retard',
            'paid' => 'Payée',
            'partially_paid' => 'Partiellement payée',
            'cancelled' => 'Annulée',
        ];

        return $labels[$this->status] ?? 'Inconnu';
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'orange',
            'overdue' => 'red',
            'paid' => 'green',
            'partially_paid' => 'blue',
            'cancelled' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Montant total payé
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->completed()->sum('amount');
    }

    /**
     * Montant restant à payer
     */
    public function getBalanceAttribute(): float
    {
        return (float) $this->amount - $this->total_paid;
    }

    /**
     * Nombre de jours de retard
     */
    public function getDaysOverdueAttribute(): int
    {
        /** @var Carbon $dueDate */
        $dueDate = $this->due_date;
        
        if ($this->status === 'paid' || $dueDate->isFuture()) {
            return 0;
        }

        return now()->diffInDays($dueDate, false);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Marquer comme payée
     */
    public function markAsPaid(?string $reference = null, ?string $method = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'amount_paid' => $this->amount,
            'payment_reference' => $reference,
            'payment_method' => $method,
        ]);
    }

    /**
     * Marquer comme partiellement payée
     */
    public function markAsPartiallyPaid(float $amount): void
    {
        $newAmountPaid = ($this->amount_paid ?? 0) + $amount;
        
        $this->update([
            'status' => $newAmountPaid >= $this->amount ? self::STATUS_PAID : self::STATUS_PARTIALLY_PAID,
            'amount_paid' => $newAmountPaid,
            'paid_at' => $newAmountPaid >= $this->amount ? now() : null,
        ]);
    }

    /**
     * Vérifier et marquer comme en retard
     */
    public function checkOverdue(): void
    {
        /** @var Carbon $dueDate */
        $dueDate = $this->due_date;
        
        if ($this->status === self::STATUS_PENDING && $dueDate->isPast()) {
            $this->update(['status' => self::STATUS_OVERDUE]);
        }
    }

    /**
     * Vérifier si entièrement payée
     */
    public function isFullyPaid(): bool
    {
        return $this->balance <= 0 || $this->status === self::STATUS_PAID;
    }

    /**
     * Vérifier si en retard
     */
    public function isOverdue(): bool
    {
        /** @var Carbon $dueDate */
        $dueDate = $this->due_date;
        
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_OVERDUE]) 
               && $dueDate->isPast();
    }

    /**
     * Annuler la facture
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason,
        ]);
    }

    // ============================================
    // BOOT METHOD
    // ============================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    /**
     * Générer un numéro de facture unique
     */
    private static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }
}