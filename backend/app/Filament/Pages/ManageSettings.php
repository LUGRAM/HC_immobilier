<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Paramètres';
    
    protected static ?string $title = 'Paramètres Système';
    
    protected static ?string $navigationGroup = 'Configuration';
    
    protected static ?int $navigationSort = 99;
    
    protected static string $view = 'filament.pages.manage-settings';
    
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'visit_price' => Setting::get('visit_price', 5000),
            'currency' => Setting::get('currency', 'XOF'),
            'reminder_24h_enabled' => Setting::get('reminder_24h_enabled', true),
            'reminder_1h_enabled' => Setting::get('reminder_1h_enabled', true),
            'max_appointments_per_day' => Setting::get('max_appointments_per_day', 10),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rendez-vous et Visites')
                    ->description('Configuration des rendez-vous de visite')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\TextInput::make('visit_price')
                            ->label('Prix d\'une visite')
                            ->numeric()
                            ->required()
                            ->suffix('FCFA')
                            ->minValue(0)
                            ->helperText('Montant fixe facturé pour chaque rendez-vous de visite')
                            ->columnSpan(1),
                        
                        Forms\Components\Select::make('currency')
                            ->label('Devise')
                            ->options([
                                'XOF' => 'Franc CFA Ouest Africain (XOF)',
                                'XAF' => 'Franc CFA Central (XAF)',
                                'EUR' => 'Euro (EUR)',
                                'USD' => 'Dollar Américain (USD)',
                            ])
                            ->required()
                            ->helperText('Devise utilisée pour toutes les transactions')
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('max_appointments_per_day')
                            ->label('Rendez-vous maximum par jour')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Limite le nombre de rendez-vous qu\'un bien peut avoir par jour')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Notifications et Rappels')
                    ->description('Configuration des notifications automatiques')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Forms\Components\Toggle::make('reminder_24h_enabled')
                            ->label('Rappel 24 heures avant')
                            ->helperText('Envoie une notification push 24h avant le rendez-vous')
                            ->inline(false),
                        
                        Forms\Components\Toggle::make('reminder_1h_enabled')
                            ->label('Rappel 1 heure avant')
                            ->helperText('Envoie une notification push 1h avant le rendez-vous')
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informations')
                    ->description('À propos de la configuration')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-gray-600 space-y-2">
                                    <p><strong>💡 Note importante :</strong></p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Les modifications prennent effet immédiatement</li>
                                        <li>Les notifications nécessitent OneSignal configuré</li>
                                        <li>Le prix des visites s\'applique à tous les nouveaux rendez-vous</li>
                                        <li>La devise ne peut pas être changée s\'il y a des transactions existantes</li>
                                    </ul>
                                </div>
                            ')),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer les paramètres')
                ->icon('heroicon-o-check')
                ->action('save')
                ->color('primary'),
            
            Action::make('reset')
                ->label('Réinitialiser')
                ->icon('heroicon-o-arrow-path')
                ->action('reset')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Réinitialiser les paramètres')
                ->modalDescription('Êtes-vous sûr de vouloir réinitialiser tous les paramètres aux valeurs par défaut ?')
                ->modalSubmitActionLabel('Oui, réinitialiser'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            foreach ($data as $key => $value) {
                $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
                $group = match($key) {
                    'visit_price', 'max_appointments_per_day' => 'appointments',
                    'reminder_24h_enabled', 'reminder_1h_enabled' => 'notifications',
                    default => 'general',
                };
                
                Setting::set($key, $value, $type, $group);
            }

            Notification::make()
                ->title('Paramètres enregistrés')
                ->body('Les paramètres système ont été mis à jour avec succès.')
                ->success()
                ->duration(5000)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->body('Une erreur s\'est produite lors de l\'enregistrement des paramètres: ' . $e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    public function reset(): void
    {
        $defaults = [
            'visit_price' => 5000,
            'currency' => 'XOF',
            'reminder_24h_enabled' => true,
            'reminder_1h_enabled' => true,
            'max_appointments_per_day' => 10,
        ];

        foreach ($defaults as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string');
            $group = match($key) {
                'visit_price', 'max_appointments_per_day' => 'appointments',
                'reminder_24h_enabled', 'reminder_1h_enabled' => 'notifications',
                default => 'general',
            };
            
            Setting::set($key, $value, $type, $group);
        }

        $this->form->fill($defaults);

        Notification::make()
            ->title('Paramètres réinitialisés')
            ->body('Les paramètres ont été restaurés aux valeurs par défaut.')
            ->success()
            ->send();
    }
}
