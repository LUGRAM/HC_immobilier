<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class LeaseResource extends Resource
{
    protected static ?string $model = Lease::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Baux';
    protected static ?string $navigationGroup = 'Immobilier';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.full_name')
                    ->label('Locataire')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('property.title')
                    ->label('Bien')
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('monthly_rent')
                    ->label('Loyer')
                    ->money('XOF'),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending_approval',
                        'success' => 'active',
                        'danger' => 'terminated',
                        'secondary' => 'expired',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_approval' => 'En attente',
                        'active' => 'Actif',
                        'terminated' => 'Terminé',
                        'expired' => 'Expiré',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_approval' => 'En attente',
                        'active' => 'Actif',
                        'terminated' => 'Terminé',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Lease $record) => $record->approve(auth()->user()))
                    ->visible(fn (Lease $record) => $record->status === 'pending_approval'),
                
                Tables\Actions\Action::make('terminate')
                    ->label('Terminer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Lease $record) => $record->terminate())
                    ->visible(fn (Lease $record) => $record->status === 'active'),
            ]);
    }
}