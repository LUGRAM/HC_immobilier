<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Utilisateurs';
    protected static ?string $navigationGroup = 'Gestion';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Prénom')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('last_name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Compte')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Rôle')
                            ->options([
                                'client' => 'Client',
                                'landlord' => 'Bailleur',
                                'admin' => 'Administrateur',
                            ])
                            ->required()
                            ->default('client'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Compte actif')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Photo de profil')
                    ->schema([
                        Forms\Components\FileUpload::make('profile_photo')
                            ->label('Photo')
                            ->image()
                            ->directory('profile-photos')
                            ->maxSize(2048),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_photo')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 
                        'https://ui-avatars.com/api/?name=' . urlencode($record->full_name)
                    ),
                
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Rôle')
                    ->colors([
                        'primary' => 'client',
                        'success' => 'landlord',
                        'danger' => 'admin',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'client' => 'Client',
                        'landlord' => 'Bailleur',
                        'admin' => 'Admin',
                        default => $state,
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        'client' => 'Client',
                        'landlord' => 'Bailleur',
                        'admin' => 'Administrateur',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Compte actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
            ])
            ->actions([
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
