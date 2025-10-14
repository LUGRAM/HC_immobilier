<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use App\Models\VisitSetting;

class VisitSettingsPage extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Paramètres Visites';
    protected static ?string $navigationGroup = 'Configuration';
    protected static string $settings = VisitSetting::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Prix des visites')
                    ->schema([
                        Forms\Components\TextInput::make('visit_price')
                            ->label('Prix fixe par visite (FCFA)')
                            ->numeric()
                            ->required()
                            ->prefix('FCFA')
                            ->helperText('Montant facturé pour chaque rendez-vous de visite'),
                    ]),

                Forms\Components\Section::make('Rappels automatiques')
                    ->schema([
                        Forms\Components\Toggle::make('auto_reminders_enabled')
                            ->label('Activer les rappels automatiques')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('reminder_hours_before')
                            ->label('Envoyer un rappel (heures avant)')
                            ->numeric()
                            ->default(24)
                            ->suffix('heures')
                            ->helperText('Nombre d\'heures avant le rendez-vous pour envoyer le rappel'),
                    ])->columns(2),

                Forms\Components\Section::make('Créneaux horaires')
                    ->schema([
                        Forms\Components\TagsInput::make('available_time_slots')
                            ->label('Créneaux disponibles')
                            ->helperText('Format: 09:00, 10:30, 14:00, etc.')
                            ->placeholder('Ajouter un créneau'),
                    ]),
            ]);
    }
}