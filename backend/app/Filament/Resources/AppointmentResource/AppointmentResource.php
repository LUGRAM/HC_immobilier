<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Rendez-vous';
    protected static ?string $navigationGroup = 'Immobilier';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.full_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('property.title')
                    ->label('Bien')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending_payment',
                        'info' => 'paid',
                        'primary' => 'confirmed',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'secondary' => 'no_show',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_payment' => 'En attente paiement',
                        'paid' => 'Payé',
                        'confirmed' => 'Confirmé',
                        'completed' => 'Complété',
                        'cancelled' => 'Annulé',
                        'no_show' => 'Absent',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Montant')
                    ->money('XOF'),
                
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Méthode')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_payment' => 'En attente paiement',
                        'paid' => 'Payé',
                        'confirmed' => 'Confirmé',
                        'completed' => 'Complété',
                        'cancelled' => 'Annulé',
                    ]),
                
                Tables\Filters\Filter::make('upcoming')
                    ->label('À venir')
                    ->query(fn ($query) => $query->where('scheduled_at', '>', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('Marquer complété')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Appointment $record) => $record->complete())
                    ->visible(fn (Appointment $record) => $record->status === 'confirmed'),
            ]);
    }
}