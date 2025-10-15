<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{User, Property, Appointment, Payment, Lease};

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $currentMonthRevenue = Payment::where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->sum('amount');
        
        $lastMonthRevenue = Payment::where('status', 'completed')
            ->whereMonth('completed_at', now()->subMonth()->month)
            ->sum('amount');
        
        $revenueChange = $lastMonthRevenue > 0 
            ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;
        
        $activeUsers = User::where('is_active', true)->count();
        $totalUsers = User::count();
        
        $appointmentsTaken = Appointment::whereMonth('created_at', now()->month)->count();
        $appointmentsConfirmed = Appointment::whereMonth('created_at', now()->month)
            ->where('status', 'confirmed')
            ->count();
        
        return [
            Stat::make('Revenus Totaux', number_format($currentMonthRevenue, 0, ',', ' ') . ' FCFA')
                ->description(
                    $revenueChange > 0 
                        ? '+' . number_format($revenueChange, 1) . '% ce mois' 
                        : number_format($revenueChange, 1) . '% ce mois'
                )
                ->descriptionIcon($revenueChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange > 0 ? 'success' : 'danger')
                ->chart([12000, 14000, 13500, 15000, $currentMonthRevenue]),
            
            Stat::make('Utilisateurs Actifs', number_format($activeUsers))
                ->description("Sur {$totalUsers} total")
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            
            Stat::make('Rendez-vous Pris', number_format($appointmentsTaken))
                ->description("{$appointmentsConfirmed} confirmés")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
            
            Stat::make('Rendez-vous Validés', number_format($appointmentsConfirmed))
                ->description(
                    $appointmentsTaken > 0 
                        ? number_format(($appointmentsConfirmed / $appointmentsTaken) * 100, 0) . '% taux conversion'
                        : '0% taux conversion'
                )
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
