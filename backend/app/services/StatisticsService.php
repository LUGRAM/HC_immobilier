<?php

namespace App\Services;

use App\Models\{Payment, Appointment, Invoice, Lease, User, Property};
use Illuminate\Support\Facades\{Http, Log, DB};
use App\Notifications\{
    AppointmentReminder,
    PaymentReceived,
    InvoiceGenerated,
    LeaseApproved
};

// ============================================
// SERVICE: StatisticsService (Analytics)
// ============================================
class StatisticsService
{
    /**
     * Statistiques globales pour l'admin
     */
    public function getGlobalStats(): array
    {
        return [
            'users' => [
                'total' => User::count(),
                'clients' => User::clients()->count(),
                'landlords' => User::landlords()->count(),
                'active' => User::active()->count(),
            ],
            'properties' => [
                'total' => Property::count(),
                'available' => Property::available()->count(),
                'rented' => Property::rented()->count(),
            ],
            'appointments' => [
                'total' => Appointment::count(),
                'pending' => Appointment::where('status', 'pending_payment')->count(),
                'completed' => Appointment::where('status', 'completed')->count(),
                'upcoming' => Appointment::upcoming()->count(),
            ],
            'leases' => [
                'total' => Lease::count(),
                'active' => Lease::active()->count(),
                'pending' => Lease::pendingApproval()->count(),
            ],
            'revenue' => [
                'today' => Payment::completed()
                                 ->whereDate('completed_at', today())
                                 ->sum('amount'),
                'this_month' => Payment::completed()
                                      ->whereMonth('completed_at', now()->month)
                                      ->whereYear('completed_at', now()->year)
                                      ->sum('amount'),
                'this_year' => Payment::completed()
                                     ->whereYear('completed_at', now()->year)
                                     ->sum('amount'),
                'all_time' => Payment::completed()->sum('amount'),
            ],
            'invoices' => [
                'pending' => Invoice::pending()->count(),
                'overdue' => Invoice::overdue()->count(),
                'paid' => Invoice::where('status', 'paid')->count(),
            ],
        ];
    }

    /**
     * Statistiques des propriétés les plus consultées
     */
    public function getMostViewedProperties(int $limit = 10)
    {
        return Property::with(['landlord', 'primaryImage'])
                      ->orderBy('views_count', 'desc')
                      ->limit($limit)
                      ->get();
    }

    /**
     * Taux de conversion (visites → baux)
     */
    public function getConversionRate(): array
    {
        $totalAppointments = Appointment::paid()->count();
        $completedAppointments = Appointment::where('status', 'completed')->count();
        $convertedToLeases = Lease::whereNotNull('appointment_id')->count();

        return [
            'total_appointments' => $totalAppointments,
            'completed_appointments' => $completedAppointments,
            'converted_to_leases' => $convertedToLeases,
            'completion_rate' => $totalAppointments > 0 
                ? round(($completedAppointments / $totalAppointments) * 100, 2)
                : 0,
            'conversion_rate' => $completedAppointments > 0
                ? round(($convertedToLeases / $completedAppointments) * 100, 2)
                : 0,
        ];
    }

    /**
     * Évolution des revenus mensuels (12 derniers mois)
     */
    public function getMonthlyRevenueChart(): array
    {
        $data = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            
            $revenue = Payment::completed()
                             ->whereYear('completed_at', $date->year)
                             ->whereMonth('completed_at', $date->month)
                             ->sum('amount');
            
            $data[] = [
                'month' => $date->translatedFormat('M Y'),
                'revenue' => (float) $revenue,
            ];
        }

        return $data;
    }

    /**
     * Répartition des paiements par méthode
     */
    public function getPaymentMethodsDistribution(): array
    {
        return Payment::completed()
                     ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
                     ->groupBy('method')
                     ->get()
                     ->toArray();
    }
}