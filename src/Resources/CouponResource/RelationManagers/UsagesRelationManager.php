<?php

namespace Noxo\FilamentCoupons\Resources\CouponResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('couponable.' . config('filament-coupons.couponable_column', 'name'))
                    ->label('Used By')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Used At')
                    ->date()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
