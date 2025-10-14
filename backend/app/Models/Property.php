<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'landlord_id',
        'title',
        'description',
        'address',
        'district',
        'city',
        'monthly_rent',
        'bedrooms',
        'bathrooms',
        'surface_area',
        'type', // ✅ Ajouté (au lieu de property_type)
        'status',
        'amenities',
        'images', // ✅ Ajouté pour stocker les URLs d'images
        'latitude',
        'longitude',
        'views_count',
        'is_featured', // ✅ Ajouté
    ];

    protected $casts = [
        'amenities' => 'array',
        'images' => 'array', // ✅ Ajouté
        'monthly_rent' => 'decimal:2',
        'surface_area' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'views_count' => 'integer',
        'is_featured' => 'boolean', // ✅ Ajouté
    ];

    // ============================================
    // CONSTANTES
    // ============================================
    
    public const TYPE_APARTMENT = 'apartment';
    public const TYPE_HOUSE = 'house';
    public const TYPE_STUDIO = 'studio';
    public const TYPE_OFFICE = 'office';
    public const TYPE_LAND = 'land';
    public const TYPE_COMMERCIAL = 'commercial';

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RENTED = 'rented';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_UNAVAILABLE = 'unavailable';

    public static array $types = [
        self::TYPE_APARTMENT,
        self::TYPE_HOUSE,
        self::TYPE_STUDIO,
        self::TYPE_OFFICE,
        self::TYPE_LAND,
        self::TYPE_COMMERCIAL,
    ];

    public static array $statuses = [
        self::STATUS_AVAILABLE,
        self::STATUS_RENTED,
        self::STATUS_MAINTENANCE,
        self::STATUS_UNAVAILABLE,
    ];

    // ============================================
    // RELATIONS
    // ============================================

    /**
     * Propriétaire du bien
     */
    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    /**
     * Images du bien (table séparée property_images)
     */
    public function propertyImages()
    {
        return $this->hasMany(PropertyImage::class)->orderBy('order');
    }

    /**
     * Image principale
     */
    public function primaryImage()
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
    }

    /**
     * Rendez-vous de visite
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Baux (contrats de location)
     */
    public function leases()
    {
        return $this->hasMany(Lease::class);
    }

    /**
     * Bail actif
     */
    public function currentLease()
    {
        return $this->hasOne(Lease::class)->where('status', 'active')->latest();
    }

    /**
     * Locataire actuel
     */
    public function currentTenant()
    {
        return $this->hasOneThrough(
            User::class,
            Lease::class,
            'property_id',
            'id',
            'id',
            'tenant_id'
        )->where('leases.status', 'active');
    }

    /**
     * Demandes de maintenance
     */
    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /**
     * Demandes de maintenance en attente
     */
    public function pendingMaintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class)->where('status', 'pending');
    }

    /**
     * Demandes de maintenance urgentes
     */
    public function urgentMaintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class)->where('priority', 'urgent');
    }

    /**
     * Factures liées aux baux
     */
    public function invoices()
    {
        return $this->hasManyThrough(Invoice::class, Lease::class);
    }

    /**
     * Paiements liés aux baux
     */
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Lease::class);
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Propriétés disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Propriétés louées
     */
    public function scopeRented($query)
    {
        return $query->where('status', self::STATUS_RENTED);
    }

    /**
     * Propriétés en vedette
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Par ville
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Par quartier
     */
    public function scopeInDistrict($query, string $district)
    {
        return $query->where('district', 'like', "%{$district}%");
    }

    /**
     * Par fourchette de prix
     */
    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('monthly_rent', [$min, $max]);
    }

    /**
     * Avec un nombre minimum de chambres
     */
    public function scopeWithBedrooms($query, int $bedrooms)
    {
        return $query->where('bedrooms', '>=', $bedrooms);
    }

    /**
     * Recherche textuelle
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%")
              ->orWhere('district', 'like', "%{$search}%");
        });
    }

    /**
     * Par propriétaire
     */
    public function scopeOwnedBy($query, int $landlordId)
    {
        return $query->where('landlord_id', $landlordId);
    }

    // ============================================
    // ACCESSORS & MUTATORS
    // ============================================

    /**
     * Libellé du type de propriété
     */
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'apartment' => 'Appartement',
            'house' => 'Maison',
            'studio' => 'Studio',
            'office' => 'Bureau',
            'land' => 'Terrain',
            'commercial' => 'Local commercial',
        ];

        return $labels[$this->type] ?? 'Autre';
    }

    /**
     * Libellé du statut
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'available' => 'Disponible',
            'rented' => 'Loué',
            'maintenance' => 'En maintenance',
            'unavailable' => 'Indisponible',
        ];

        return $labels[$this->status] ?? 'Inconnu';
    }

    /**
     * Couleur du statut
     */
    public function getStatusColorAttribute(): string
    {
        $colors = [
            'available' => 'green',
            'rented' => 'blue',
            'maintenance' => 'orange',
            'unavailable' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Prix formaté
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->monthly_rent, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Surface formatée
     */
    public function getFormattedSurfaceAttribute(): string
    {
        return $this->surface_area ? number_format((float) $this->surface_area, 1) . ' m²' : 'N/A';
    }

    /**
     * URL de l'image principale
     */
    public function getMainImageUrlAttribute(): string
    {
        // Si images JSON existe et contient des URLs
        if ($this->images && is_array($this->images) && count($this->images) > 0) {
            return asset('storage/' . $this->images[0]);
        }

        // Sinon chercher dans la table property_images
        $primaryImage = $this->primaryImage;
        if ($primaryImage) {
            return asset('storage/' . $primaryImage->image_path);
        }

        // Image par défaut
        return asset('images/property-placeholder.jpg');
    }

    /**
     * Toutes les URLs d'images
     */
    public function getImageUrlsAttribute(): array
    {
        $urls = [];

        // Images depuis le champ JSON
        if ($this->images && is_array($this->images)) {
            foreach ($this->images as $image) {
                $urls[] = asset('storage/' . $image);
            }
        }

        // Images depuis la table property_images
        foreach ($this->propertyImages as $image) {
            $urls[] = asset('storage/' . $image->image_path);
        }

        return $urls;
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Incrémenter le nombre de vues
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Vérifier si disponible
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Vérifier si loué
     */
    public function isRented(): bool
    {
        return $this->status === self::STATUS_RENTED;
    }

    /**
     * Vérifier si en maintenance
     */
    public function isInMaintenance(): bool
    {
        return $this->status === self::STATUS_MAINTENANCE;
    }

    /**
     * Marquer comme disponible
     */
    public function markAsAvailable(): void
    {
        $this->update(['status' => self::STATUS_AVAILABLE]);
    }

    /**
     * Marquer comme loué
     */
    public function markAsRented(): void
    {
        $this->update(['status' => self::STATUS_RENTED]);
    }

    /**
     * Mettre en vedette
     */
    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    /**
     * Retirer de la vedette
     */
    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }

    /**
     * Obtenir le locataire actuel
     */
    public function getCurrentTenant()
    {
        $activeLease = $this->currentLease;
        return $activeLease ? $activeLease->tenant : null;
    }

    /**
     * Vérifier si le bien a un locataire
     */
    public function hasTenant(): bool
    {
        return $this->currentLease()->exists();
    }

    /**
     * Calculer le revenu total généré
     */
    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->payments()->where('status', 'completed')->sum('amount');
    }

    /**
     * Obtenir le nombre de demandes de maintenance en attente
     */
    public function getPendingMaintenanceCountAttribute(): int
    {
        return $this->pendingMaintenanceRequests()->count();
    }
}