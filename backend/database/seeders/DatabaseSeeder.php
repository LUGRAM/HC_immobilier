<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Property;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ne seeder qu'en environnement local
        if (!app()->environment('local')) {
            $this->command->warn('⚠️  Seeding désactivé en production !');
            return;
        }

        $this->command->info('🌱 Début du seeding...');
        $this->command->newLine();

        // ========================================
        // CRÉER LES UTILISATEURS
        // ========================================
        
        $this->command->info('👥 Création des utilisateurs...');

        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'HC',
            'email' => 'admin@hc.com',
            'password' => Hash::make('password'),
            'phone' => '+241 07 00 00 00',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $landlord1 = User::create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => Hash::make('password'),
            'phone' => '+241 07 11 11 11',
            'role' => 'landlord',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $landlord2 = User::create([
            'first_name' => 'Marie',
            'last_name' => 'Martin',
            'email' => 'marie.martin@example.com',
            'password' => Hash::make('password'),
            'phone' => '+241 07 22 22 22',
            'role' => 'landlord',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $tenant1 = User::create([
            'first_name' => 'Paul',
            'last_name' => 'Lescure',
            'email' => 'paul.lescure@example.com',
            'password' => Hash::make('password'),
            'phone' => '+241 07 33 33 33',
            'role' => 'tenant',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $tenant2 = User::create([
            'first_name' => 'Sophie',
            'last_name' => 'Bernard',
            'email' => 'sophie.bernard@example.com',
            'password' => Hash::make('password'),
            'phone' => '+241 07 44 44 44',
            'role' => 'tenant',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $tenant3 = User::create([
            'first_name' => 'Thomas',
            'last_name' => 'Petit',
            'email' => 'thomas.petit@example.com',
            'password' => Hash::make('password'),
            'phone' => '+241 07 55 55 55',
            'role' => 'tenant',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Clients (visiteurs)
        $client1 = User::create([
            'first_name' => 'Alice',
            'last_name' => 'Moreau',
            'email' => 'alice.moreau@example.com',
            'password' => Hash::make('password'),
            'phone' => '+241 07 66 66 66',
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('   ✅ ' . User::count() . ' utilisateurs créés');

        // ========================================
        // CRÉER LES PROPRIÉTÉS
        // ========================================
        
        $this->command->info('🏠 Création des propriétés...');

        $property1 = Property::create([
            'landlord_id' => $landlord1->id,
            'title' => 'Appartement 2 pièces - Centre-ville Libreville',
            'description' => 'Bel appartement moderne au cœur de Libreville, proche de toutes commodités. Cuisine équipée, climatisation, balcon avec vue.',
            'type' => 'apartment',
            'status' => 'rented',
            'monthly_rent' => 200000,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'surface_area' => 65.5,
            'address' => 'Avenue du Général de Gaulle',
            'city' => 'Libreville',
            'district' => 'Centre-ville',
            'amenities' => ['wifi', 'parking', 'climatisation', 'cuisine équipée', 'balcon'],
            'is_featured' => true,
            'views_count' => 45,
        ]);

        $property2 = Property::create([
            'landlord_id' => $landlord1->id,
            'title' => 'Villa 4 chambres - Batterie IV',
            'description' => 'Grande villa spacieuse avec jardin, idéale pour famille. Garage, piscine, sécurité 24/7.',
            'type' => 'house',
            'status' => 'available',
            'monthly_rent' => 500000,
            'bedrooms' => 4,
            'bathrooms' => 3,
            'surface_area' => 200,
            'address' => 'Rue de la Paix, Batterie IV',
            'city' => 'Libreville',
            'district' => 'Batterie IV',
            'amenities' => ['jardin', 'garage', 'piscine', 'sécurité', 'wifi', 'climatisation'],
            'is_featured' => true,
            'views_count' => 78,
        ]);

        $property3 = Property::create([
            'landlord_id' => $landlord2->id,
            'title' => 'Studio meublé - Quaben',
            'description' => 'Studio tout équipé, parfait pour étudiant ou jeune professionnel. Wifi inclus, meublé moderne.',
            'type' => 'studio',
            'status' => 'available',
            'monthly_rent' => 120000,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'surface_area' => 30,
            'address' => 'Quartier Quaben, près université',
            'city' => 'Libreville',
            'district' => 'Quaben',
            'amenities' => ['meublé', 'wifi', 'climatisation', 'parking'],
            'views_count' => 32,
        ]);

        $property4 = Property::create([
            'landlord_id' => $landlord2->id,
            'title' => 'Bureau professionnel - Glass',
            'description' => 'Espace de bureau moderne, idéal pour startup ou PME. Parking, climatisation, salle de réunion.',
            'type' => 'office',
            'status' => 'available',
            'monthly_rent' => 350000,
            'bedrooms' => 0,
            'bathrooms' => 2,
            'surface_area' => 80,
            'address' => 'Boulevard Triomphal, Immeuble Vision',
            'city' => 'Libreville',
            'district' => 'Glass',
            'amenities' => ['parking', 'wifi', 'climatisation', 'salle de réunion', 'ascenseur'],
            'views_count' => 25,
        ]);

        $property5 = Property::create([
            'landlord_id' => $landlord1->id,
            'title' => 'Appartement 3 pièces - Mont-Bouët',
            'description' => 'Spacieux appartement familial dans quartier calme. 3 chambres, cuisine américaine.',
            'type' => 'apartment',
            'status' => 'available',
            'monthly_rent' => 280000,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'surface_area' => 95,
            'address' => 'Mont-Bouët, Résidence les Palmiers',
            'city' => 'Libreville',
            'district' => 'Mont-Bouët',
            'amenities' => ['parking', 'wifi', 'climatisation', 'gardien'],
            'views_count' => 18,
        ]);

        $property6 = Property::create([
            'landlord_id' => $landlord2->id,
            'title' => 'Maison 2 chambres - Owendo',
            'description' => 'Petite maison charmante avec cour. Quartier tranquille, proche du marché.',
            'type' => 'house',
            'status' => 'rented',
            'monthly_rent' => 180000,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'surface_area' => 70,
            'address' => 'Owendo, quartier résidentiel',
            'city' => 'Libreville',
            'district' => 'Owendo',
            'amenities' => ['cour', 'parking', 'eau', 'électricité'],
            'views_count' => 12,
        ]);

        $this->command->info('   ✅ ' . Property::count() . ' propriétés créées');

        // ========================================
        // CRÉER LES BAUX
        // ========================================
        
        $this->command->info('📋 Création des baux...');

        $lease1 = Lease::create([
            'property_id' => $property1->id,
            'tenant_id' => $tenant1->id,
            'landlord_id' => $landlord1->id,
            'start_date' => now()->subMonths(3),
            'end_date' => now()->addMonths(9),
            'monthly_rent' => 200000,
            'deposit' => 400000,
            'status' => 'active',
        ]);

        $lease2 = Lease::create([
            'property_id' => $property6->id,
            'tenant_id' => $tenant2->id,
            'landlord_id' => $landlord2->id,
            'start_date' => now()->subMonths(6),
            'end_date' => now()->addMonths(6),
            'monthly_rent' => 180000,
            'deposit' => 360000,
            'status' => 'active',
        ]);

        $this->command->info('   ✅ ' . Lease::count() . ' baux créés');

        // ========================================
        // CRÉER DES DEMANDES DE MAINTENANCE
        // ========================================
        
        $this->command->info('🔧 Création des demandes de maintenance...');

        MaintenanceRequest::create([
            'property_id' => $property1->id,
            'tenant_id' => $tenant1->id,
            'title' => 'Fuite d\'eau dans la cuisine',
            'description' => 'Il y a une fuite importante sous l\'évier de la cuisine depuis hier soir. L\'eau coule en continu.',
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'pending',
        ]);

        MaintenanceRequest::create([
            'property_id' => $property1->id,
            'tenant_id' => $tenant1->id,
            'title' => 'Climatisation ne fonctionne plus',
            'description' => 'La climatisation de la chambre principale ne démarre plus. Fait un bruit étrange.',
            'category' => 'hvac',
            'priority' => 'medium',
            'status' => 'in_progress',
            'scheduled_date' => now()->addDays(2),
        ]);

        MaintenanceRequest::create([
            'property_id' => $property1->id,
            'tenant_id' => $tenant1->id,
            'title' => 'Ampoule grillée dans le salon',
            'description' => 'L\'ampoule principale du salon est grillée. Besoin de remplacement.',
            'category' => 'electrical',
            'priority' => 'low',
            'status' => 'completed',
            'completed_date' => now()->subDays(2),
            'resolution_notes' => 'Ampoule LED remplacée. Tout fonctionne correctement.',
            'cost' => 5000,
        ]);

        MaintenanceRequest::create([
            'property_id' => $property6->id,
            'tenant_id' => $tenant2->id,
            'title' => 'Porte d\'entrée difficile à fermer',
            'description' => 'La serrure de la porte d\'entrée est défectueuse, très difficile à fermer.',
            'category' => 'structural',
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        MaintenanceRequest::create([
            'property_id' => $property6->id,
            'tenant_id' => $tenant2->id,
            'title' => 'Robinet qui fuit dans la salle de bain',
            'description' => 'Le robinet du lavabo fuit légèrement mais en continu.',
            'category' => 'plumbing',
            'priority' => 'low',
            'status' => 'in_progress',
            'scheduled_date' => now()->addDays(3),
        ]);

        $this->command->info('   ✅ ' . MaintenanceRequest::count() . ' demandes de maintenance créées');

        // ========================================
        // RÉSUMÉ
        // ========================================
        
        $this->command->newLine();
        $this->command->info('✅ Données de test créées avec succès !');
        $this->command->newLine();
        $this->command->info('📊 Statistiques:');
        $this->command->table(
            ['Type', 'Nombre'],
            [
                ['Utilisateurs', User::count()],
                ['  - Admins', User::where('role', 'admin')->count()],
                ['  - Bailleurs', User::where('role', 'landlord')->count()],
                ['  - Locataires', User::where('role', 'tenant')->count()],
                ['  - Clients', User::where('role', 'client')->count()],
                ['Propriétés', Property::count()],
                ['  - Disponibles', Property::where('status', 'available')->count()],
                ['  - Louées', Property::where('status', 'rented')->count()],
                ['Baux actifs', Lease::where('status', 'active')->count()],
                ['Demandes de maintenance', MaintenanceRequest::count()],
            ]
        );
        
        $this->command->newLine();
        $this->command->info('🔐 Identifiants de connexion:');
        $this->command->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Admin', 'admin@hc.com', 'password'],
                ['Bailleur 1', 'jean.dupont@example.com', 'password'],
                ['Bailleur 2', 'marie.martin@example.com', 'password'],
                ['Locataire 1', 'paul.lescure@example.com', 'password'],
                ['Locataire 2', 'sophie.bernard@example.com', 'password'],
                ['Client', 'alice.moreau@example.com', 'password'],
            ]
        );

        $this->command->newLine();
        $this->command->info('🚀 Vous pouvez maintenant lancer le serveur :');
        $this->command->comment('   php artisan serve');
        $this->command->newLine();
    }
}