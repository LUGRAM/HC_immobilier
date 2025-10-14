<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{User, Property, Appointment, Payment, Lease};

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Utilisateurs', User::count())
                ->description('Clients et bailleurs')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            
            Stat::make('Biens Disponibles', Property::available()->count())
                ->description(Property::rented()->count() . ' loués')
                ->descriptionIcon('heroicon-m-home')
                ->color('success'),
            
            Stat::make('Rendez-vous ce mois', 
                Appointment::whereMonth('created_at', now()->month)->count()
            )
                ->description('Total: ' . Appointment::count())
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
            
            Stat::make('Revenus ce mois',
                Payment::completed()
                    ->whereMonth('completed_at', now()->month)
                    ->sum('amount') . ' FCFA'
            )
                ->description('Paiements complétés')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}