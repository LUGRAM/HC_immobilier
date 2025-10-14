<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Factures';
    protected static ?string $navigationGroup = 'Finances';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('N° Facture')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tenant.full_name')
                    ->label('Locataire')
                    ->searchable(),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'rent',
                        'info' => 'water',
                        'warning' => 'electricity',
                        'secondary' => 'other',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'rent' => 'Loyer',
                        'water' => 'Eau',
                        'electricity' => 'Électricité',
                        'other' => 'Autre',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'secondary' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'En attente',
                        'paid' => 'Payé',
                        'overdue' => 'En retard',
                        'cancelled' => 'Annulé',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Payé le')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'En attente',
                        'paid' => 'Payé',
                        'overdue' => 'En retard',
                    ]),
                
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'rent' => 'Loyer',
                        'water' => 'Eau',
                        'electricity' => 'Électricité',
                        'other' => 'Autre',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}