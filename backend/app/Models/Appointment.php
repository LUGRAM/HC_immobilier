<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'property_id',
        'landlord_id', // ✅ Ajouté
        'scheduled_at',
        'status',
        'amount_paid',
        'payment_reference',
        'payment_method',
        'paid_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'reminder_sent_at',
        'client_notes',
        'admin_notes'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    // ============================================
    // CONSTANTES
    // ============================================
    
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PAID = 'paid';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    // ============================================
    // RELATIONS
    // ============================================

    /**
     * Client qui prend le rendez-vous
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Propriété visitée
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Bailleur de la propriété
     */
    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    /**
     * Paiement lié (relation polymorphique)
     */
    public function payment()
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    /**
     * Paiements (plusieurs paiements possibles)
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Bail créé suite à ce rendez-vous
     */
    public function lease()
    {
        return $this->hasOne(Lease::class);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePaid($query)
    {
        return $query->whereIn('status', [self::STATUS_PAID, self::STATUS_CONFIRMED, self::STATUS_COMPLETED]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                     ->whereIn('status', [self::STATUS_PAID, self::STATUS_CONFIRMED]);
    }

    public function scopePast($query)
    {
        return $query->where('scheduled_at', '<', now());
    }

    public function scopeNeedingReminder($query)
    {
        // Rappel 24h avant
        $reminderTime = now()->addHours(24);
        
        return $query->whereNull('reminder_sent_at')
                     ->where('scheduled_at', '<=', $reminderTime)
                     ->where('scheduled_at', '>', now())
                     ->whereIn('status', [self::STATUS_PAID, self::STATUS_CONFIRMED]);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForLandlord($query, int $landlordId)
    {
        return $query->where('landlord_id', $landlordId);
    }

    // ============================================
    // ACCESSORS & MUTATORS
    // ============================================

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'paid' => 'Payé',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'no_show' => 'Absent',
        ];

        return $labels[$this->status] ?? 'Inconnu';
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'orange',
            'confirmed' => 'blue',
            'paid' => 'green',
            'completed' => 'green',
            'cancelled' => 'red',
            'no_show' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Marquer comme payé
     */
    public function markAsPaid(string $reference, string $method): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'payment_reference' => $reference,
            'payment_method' => $method,
            'paid_at' => now(),
        ]);
    }

    /**
     * Confirmer le rendez-vous
     */
    public function confirm(): void
    {
        $this->update(['status' => self::STATUS_CONFIRMED]);
    }

    /**
     * Marquer comme terminé
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Annuler le rendez-vous
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Marquer comme absent (no-show)
     */
    public function markAsNoShow(): void
    {
        $this->update(['status' => self::STATUS_NO_SHOW]);
    }

    /**
     * Envoyer un rappel
     */
    public function sendReminder(): void
    {
        $this->update(['reminder_sent_at' => now()]);
        // Logique d'envoi de notification
    }

    /**
     * Vérifier si un bail peut être créé
     */
    public function canCreateLease(): bool
    {
        return $this->status === self::STATUS_COMPLETED && !$this->lease;
    }

    /**
     * Vérifier si payé
     */
    public function isPaid(): bool
    {
        return $this->payments()->completed()->exists();
    }

    /**
     * Vérifier si annulable
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_PAID]);
    }
}