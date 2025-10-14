<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'tenant_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'images',
        'scheduled_date',
        'completed_date',
        'resolution_notes',
        'cost',
        'assigned_to',
    ];

    protected $casts = [
        'images' => 'array',
        'scheduled_date' => 'datetime',
        'completed_date' => 'datetime',
        'cost' => 'decimal:2',
    ];

    /**
     * Relations
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Accessors & Mutators
     */
    public function getCategoryLabelAttribute()
    {
        $labels = [
            'plumbing' => 'Plomberie',
            'electrical' => 'Électricité',
            'hvac' => 'Climatisation',
            'appliance' => 'Électroménager',
            'structural' => 'Structure',
            'security' => 'Sécurité',
            'other' => 'Autre',
        ];

        return $labels[$this->category] ?? 'Autre';
    }

    public function getPriorityLabelAttribute()
    {
        $labels = [
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
        ];

        return $labels[$this->priority] ?? 'Moyenne';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'En attente',
            'in_progress' => 'En cours',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
        ];

        return $labels[$this->status] ?? 'En attente';
    }

    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'urgent' => 'red',
        ];

        return $colors[$this->priority] ?? 'gray';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'in_progress' => 'blue',
            'completed' => 'green',
            'cancelled' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Helpers
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isUrgent()
    {
        return $this->priority === 'urgent';
    }

    public function isHighPriority()
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    public function canBeUpdated()
    {
        return !in_array($this->status, ['completed', 'cancelled']);
    }

    public function markAsInProgress()
    {
        $this->update(['status' => 'in_progress']);
    }

    public function markAsCompleted($resolutionNotes = null, $cost = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
            'resolution_notes' => $resolutionNotes,
            'cost' => $cost,
        ]);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function assignTo(User $user)
    {
        $this->update(['assigned_to' => $user->id]);
    }
}