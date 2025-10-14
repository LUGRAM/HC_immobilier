<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

// ============================================
// MODEL: Lease
// ============================================
class Lease extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'tenant_id', 'landlord_id', 'appointment_id',
        'start_date', 'end_date', 'monthly_rent', 'security_deposit',
        'status', 'terms', 'approved_at', 'approved_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relations
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Helpers
    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);

        // Mettre à jour le statut de la propriété
        $this->property->update(['status' => 'rented']);
    }

    public function terminate(): void
    {
        $this->update(['status' => 'terminated']);
        $this->property->update(['status' => 'available']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
