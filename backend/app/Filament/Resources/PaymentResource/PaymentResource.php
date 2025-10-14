<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Paiements';
    protected static ?string $navigationGroup = 'Finances';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction')
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Utilisateur')
                    ->searchable(),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'visit',
                        'success' => 'rent',
                        'info' => fn ($state) => in_array($state, ['water', 'electricity']),
                    ]),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('method')
                    ->label('Méthode')
                    ->colors([
                        'primary' => 'mobile_money',
                        'success' => 'cash',
                        'info' => 'bank_transfer',
                    ]),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Complété le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'En attente',
                        'processing' => 'En traitement',
                        'completed' => 'Complété',
                        'failed' => 'Échoué',
                    ]),
                
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'mobile_money' => 'Mobile Money',
                        'cash' => 'Espèces',
                        'bank_transfer' => 'Virement',
                    ]),
            ]);
    }
}