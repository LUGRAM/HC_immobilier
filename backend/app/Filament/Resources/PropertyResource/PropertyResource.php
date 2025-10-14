<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Property;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Biens';
    protected static ?string $navigationGroup = 'Immobilier';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\Select::make('landlord_id')
                            ->label('Bailleur')
                            ->relationship('landlord', 'email')
                            ->searchable()
                            ->required(),
                        
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(4),
                    ]),

                Forms\Components\Section::make('Localisation')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('district')
                            ->label('Quartier')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Caractéristiques')
                    ->schema([
                        Forms\Components\TextInput::make('monthly_rent')
                            ->label('Loyer mensuel (FCFA)')
                            ->numeric()
                            ->required()
                            ->prefix('FCFA'),
                        
                        Forms\Components\TextInput::make('bedrooms')
                            ->label('Nombre de chambres')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        
                        Forms\Components\TextInput::make('bathrooms')
                            ->label('Nombre de salles de bain')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        
                        Forms\Components\TextInput::make('surface_area')
                            ->label('Surface (m²)')
                            ->numeric()
                            ->suffix('m²'),
                        
                        Forms\Components\Select::make('property_type')
                            ->label('Type de bien')
                            ->options([
                                'apartment' => 'Appartement',
                                'house' => 'Maison',
                                'studio' => 'Studio',
                                'villa' => 'Villa',
                            ])
                            ->required(),
                        
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'available' => 'Disponible',
                                'rented' => 'Loué',
                                'maintenance' => 'Maintenance',
                                'unavailable' => 'Indisponible',
                            ])
                            ->required()
                            ->default('available'),
                    ])->columns(3),

                Forms\Components\Section::make('Équipements')
                    ->schema([
                        Forms\Components\CheckboxList::make('amenities')
                            ->label('Équipements disponibles')
                            ->options([
                                'wifi' => 'WiFi',
                                'parking' => 'Parking',
                                'garden' => 'Jardin',
                                'pool' => 'Piscine',
                                'security' => 'Sécurité',
                                'elevator' => 'Ascenseur',
                                'air_conditioning' => 'Climatisation',
                                'furnished' => 'Meublé',
                            ])
                            ->columns(4),
                    ]),

                Forms\Components\Section::make('Coordonnées GPS')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric(),
                        
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric(),
                    ])->columns(2),

                Forms\Components\Section::make('Images')
                    ->schema([
                        Forms\Components\Repeater::make('images')
                            ->relationship('images')
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Image')
                                    ->image()
                                    ->directory('property-images')
                                    ->required(),
                                
                                Forms\Components\TextInput::make('order')
                                    ->label('Ordre')
                                    ->numeric()
                                    ->default(0),
                                
                                Forms\Components\Toggle::make('is_primary')
                                    ->label('Image principale')
                                    ->default(false),
                            ])
                            ->columns(3)
                            ->defaultItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('primaryImage.image_path')
                    ->label('Image')
                    ->square(),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('district')
                    ->label('Quartier')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('monthly_rent')
                    ->label('Loyer')
                    ->money('XOF')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('bedrooms')
                    ->label('Chambres')
                    ->suffix(' ch.')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'available',
                        'danger' => 'rented',
                        'warning' => 'maintenance',
                        'secondary' => 'unavailable',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'rented' => 'Loué',
                        'maintenance' => 'Maintenance',
                        'unavailable' => 'Indisponible',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Vues')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('landlord.full_name')
                    ->label('Bailleur')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'available' => 'Disponible',
                        'rented' => 'Loué',
                        'maintenance' => 'Maintenance',
                        'unavailable' => 'Indisponible',
                    ]),
                
                Tables\Filters\SelectFilter::make('district')
                    ->label('Quartier')
                    ->options(fn () => Property::distinct()->pluck('district', 'district')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}