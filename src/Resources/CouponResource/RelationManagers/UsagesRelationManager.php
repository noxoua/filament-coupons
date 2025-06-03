<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Resources\CouponResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament-coupons::filament-coupons.resource.usages');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('id', 'desc')
            ->modelLabel(__('filament-coupons::filament-coupons.resource.usage'))
            ->pluralModelLabel(__('filament-coupons::filament-coupons.resource.usages'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('couponable.'.config('filament-coupons.couponable_column', 'name'))
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.used_by'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.used_at'))
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
