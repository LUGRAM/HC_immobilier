<?php

namespace App\Filament\Resources;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceRequestResource extends Resource
{
    protected static ?string $model = MaintenanceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    
    protected static ?string $navigationLabel = 'Demandes de Maintenance';
    
    protected static ?string $navigationGroup = 'Gestion Locative';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\Select::make('property_id')
                            ->label('Propriété')
                            ->relationship('property', 'title')
                            ->searchable()
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('tenant_id')
                            ->label('Locataire')
                            ->relationship('tenant', 'first_name')
                            ->searchable()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->preload(),

                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Classification')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'plumbing' => 'Plomberie',
                                'electrical' => 'Électricité',
                                'hvac' => 'Climatisation',
                                'appliance' => 'Électroménager',
                                'structural' => 'Structure',
                                'security' => 'Sécurité',
                                'other' => 'Autre',
                            ])
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->label('Priorité')
                            ->options([
                                'low' => 'Faible',
                                'medium' => 'Moyenne',
                                'high' => 'Haute',
                                'urgent' => 'Urgente',
                            ])
                            ->required()
                            ->default('medium'),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'in_progress' => 'En cours',
                                'completed' => 'Terminée',
                                'cancelled' => 'Annulée',
                            ])
                            ->required()
                            ->default('pending'),

                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigné à')
                            ->options(User::all()->pluck('full_name', 'id'))
                            ->searchable()
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Planification')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_date')
                            ->label('Date planifiée')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('completed_date')
                            ->label('Date de  complétion')
                            ->nullable()
                            ->disabled(fn ($get) => $get('status') !== 'completed'),

                        Forms\Components\TextInput::make('cost')
                            ->label('Coût (FCFA)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->nullable(),
                    ])->columns(3),

                Forms\Components\Section::make('Résolution')
                    ->schema([
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Notes de résolution')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('images')
                            ->label('Photos')
                            ->image()
                            ->multiple()
                            ->directory('maintenance-requests')
                            ->maxFiles(5)
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('property.title')
                    ->label('Propriété')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('tenant.full_name')
                    ->label('Locataire')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'plumbing' => 'Plomberie',
                        'electrical' => 'Électricité',
                        'hvac' => 'Climatisation',
                        'appliance' => 'Électroménager',
                        'structural' => 'Structure',
                        'security' => 'Sécurité',
                        'other' => 'Autre',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorité')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger' => fn ($state) => in_array($state, ['high', 'urgent']),
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'low' => 'Faible',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'secondary' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'En attente',
                        'in_progress' => 'En cours',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Planifié')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'in_progress' => 'En cours',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options([
                        'low' => 'Faible',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'plumbing' => 'Plomberie',
                        'electrical' => 'Électricité',
                        'hvac' => 'Climatisation',
                        'appliance' => 'Électroménager',
                        'structural' => 'Structure',
                        'security' => 'Sécurité',
                        'other' => 'Autre',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('mark_in_progress')
                    ->label('En cours')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->markAsInProgress()),

                Tables\Actions\Action::make('mark_completed')
                    ->label('Terminée')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'in_progress')
                    ->action(fn ($record) => $record->markAsCompleted()),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\MaintenanceRequestResource\Pages\ListMaintenanceRequests::route('/'),
            'create' => \App\Filament\Resources\MaintenanceRequestResource\Pages\CreateMaintenanceRequest::route('/create'),
            'edit' => \App\Filament\Resources\MaintenanceRequestResource\Pages\EditMaintenanceRequest::route('/{record}/edit'),
            'view' => \App\Filament\Resources\MaintenanceRequestResource\Pages\ViewMaintenanceRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['property', 'tenant', 'assignedTo']);
    }
}
