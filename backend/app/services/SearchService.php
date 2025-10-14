<?php

namespace App\Services;

use App\Models\{Payment, Appointment,  User, Property};

// ============================================
// SERVICE: SearchService (Recherche avancée)
// ============================================
class SearchService
{
    /**
     * Recherche avancée de propriétés avec filtres
     */
    public function searchProperties(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Property::with(['landlord', 'primaryImage', 'images'])
                        ->available();

        // Recherche textuelle
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filtre par quartier
        if (!empty($filters['district'])) {
            $query->inDistrict($filters['district']);
        }

        // Filtre par ville
        if (!empty($filters['city'])) {
            $query->where('city', 'like', "%{$filters['city']}%");
        }

        // Filtre par fourchette de prix
        if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
            $query->priceRange($filters['min_price'], $filters['max_price']);
        }

        // Filtre par nombre de chambres
        if (!empty($filters['bedrooms'])) {
            $query->withBedrooms($filters['bedrooms']);
        }

        // Filtre par type de propriété
        if (!empty($filters['property_type'])) {
            $query->where('property_type', $filters['property_type']);
        }

        // Filtre par équipements
        if (!empty($filters['amenities']) && is_array($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Suggestions basées sur l'historique de recherche
     */
    public function getSuggestions(User $user): array
    {
        // Analyser les rendez-vous passés et préférences
        $pastAppointments = Appointment::where('client_id', $user->id)
                                      ->with('property')
                                      ->latest()
                                      ->limit(5)
                                      ->get();

        // Extraire les caractéristiques communes
        $avgPrice = $pastAppointments->avg('property.monthly_rent');
        $commonDistricts = $pastAppointments->pluck('property.district')
                                           ->countBy()
                                           ->sortDesc()
                                           ->keys()
                                           ->take(3);

        // Trouver des propriétés similaires
        $suggestions = Property::available()
                              ->when($avgPrice, function($q) use ($avgPrice) {
                                  $q->whereBetween('monthly_rent', [
                                      $avgPrice * 0.8,
                                      $avgPrice * 1.2
                                  ]);
                              })
                              ->when($commonDistricts->isNotEmpty(), function($q) use ($commonDistricts) {
                                  $q->whereIn('district', $commonDistricts);
                              })
                              ->with(['primaryImage'])
                              ->inRandomOrder()
                              ->limit(5)
                              ->get();

        return $suggestions->toArray();
    }
}