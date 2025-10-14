<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// ============================================
// MODEL: User
// ============================================
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'profile_photo',
        'is_active',
        'onesignal_player_id',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];


    /**
     * Rôles disponibles
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_LANDLORD = 'landlord';
    public const ROLE_TENANT = 'tenant';
    public const ROLE_CLIENT = 'client';

    /**
     * Liste des rôles valides
     */
    public static array $availableRoles = [
        self::ROLE_ADMIN,
        self::ROLE_LANDLORD,
        self::ROLE_TENANT,
        self::ROLE_CLIENT,
    ];

    /**
     * Valeur par défaut pour role
     */
    protected $attributes = [
        'role' => self::ROLE_CLIENT,
        'is_active' => true,
    ];
    

    // ============================================
    // RELATIONS
    // ============================================

    /**
     * Propriétés possédées (pour les bailleurs)
     */
    public function ownedProperties()
    {
        return $this->hasMany(Property::class, 'landlord_id');
    }

    /**
     * Alias pour ownedProperties
     */
    public function properties()
    {
        return $this->ownedProperties();
    }

    /**
     * Rendez-vous créés par l'utilisateur (clients)
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }

    /**
     * Rendez-vous reçus (bailleurs)
     */
    public function landlordAppointments()
    {
        return $this->hasMany(Appointment::class, 'landlord_id');
    }

    /**
     * Baux en tant que locataire
     */
    public function leases()
    {
        return $this->hasMany(Lease::class, 'tenant_id');
    }

    /**
     * Baux en tant que bailleur
     */
    public function landlordLeases()
    {
        return $this->hasMany(Lease::class, 'landlord_id');
    }

    /**
     * Factures du locataire
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'tenant_id');
    }

    /**
     * Dépenses (pour les bailleurs)
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'landlord_id');
    }

    /**
     * Paiements effectués
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Tokens de périphériques (push notifications)
     */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Demandes de maintenance créées (locataires)
     */
    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'tenant_id');
    }

    /**
     * Demandes de maintenance assignées (techniciens)
     */
    public function assignedMaintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'assigned_to');
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope pour les clients
     */
    public function scopeClients($query)
    {
        return $query->where('role', 'client');
    }

    /**
     * Scope pour les locataires (tenants)
     */
    public function scopeTenants($query)
    {
        return $query->where('role', 'tenant');
    }

    /**
     * Scope pour les bailleurs
     */
    public function scopeLandlords($query)
    {
        return $query->where('role', 'landlord');
    }

    /**
     * Scope pour les admins
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope pour les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ============================================
    // ACCESSORS & MUTATORS
    // ============================================

    /**
     * Obtenir le nom complet
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Obtenir l'URL de la photo de profil
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }
        
        // Avatar par défaut avec initiales
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&color=7F9CF5&background=EBF4FF';
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Vérifier si l'utilisateur est un client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Vérifier si l'utilisateur est un locataire
     */
    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    /**
     * Vérifier si l'utilisateur est un bailleur
     */
    public function isLandlord(): bool
    {
        return $this->role === 'landlord';
    }

    /**
     * Vérifier si l'utilisateur est un admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est actif
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Vérifier si l'utilisateur a des baux actifs
     */
    public function hasActiveLeases(): bool
    {
        return $this->leases()->where('status', 'active')->exists();
    }

    /**
     * Obtenir le bail actif de l'utilisateur
     */
    public function getActiveLease()
    {
        return $this->leases()->where('status', 'active')->first();
    }

    /**
     * Vérifier si l'utilisateur possède une propriété
     */
    public function ownsProperty(int $propertyId): bool
    {
        return $this->ownedProperties()->where('id', $propertyId)->exists();
    }

    /**
     * Vérifier si l'utilisateur loue une propriété
     */
    public function rentsProperty(int $propertyId): bool
    {
        return $this->leases()
            ->where('property_id', $propertyId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Obtenir le nombre de propriétés possédées
     */
    public function getPropertiesCountAttribute(): int
    {
        return $this->ownedProperties()->count();
    }

    /**
     * Obtenir le nombre de locataires (pour bailleurs)
     */
    public function getTenantsCountAttribute(): int
    {
        return $this->landlordLeases()
            ->where('status', 'active')
            ->distinct('tenant_id')
            ->count('tenant_id');
    }

    /**
     * Obtenir le total des revenus mensuels (pour bailleurs)
     */
    public function getMonthlyRevenueAttribute(): float
    {
        return (float) $this->landlordLeases()
            ->where('status', 'active')
            ->sum('monthly_rent');
    }

    /**
     * Obtenir les factures impayées
     */
    public function getUnpaidInvoicesAttribute()
    {
        return $this->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->get();
    }

    /**
     * Obtenir le montant total dû
     */
    public function getTotalDueAttribute(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');
    }

    /**
     * Mettre à jour le dernier login
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Activer/Désactiver le compte
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}