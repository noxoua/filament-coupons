<?php

namespace Noxo\FilamentCoupons\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Resources\CouponResource\Pages;
use Noxo\FilamentCoupons\Resources\CouponResource\RelationManagers;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Section::make()
                            ->heading('Details')
                            ->compact()
                            ->columns(2)
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(20)
                                    // TODO: ->required() not working as expected with disabled condition
                                    ->required(fn (string $operation, Get $get) => ! ($operation === 'create' && $get('number_of_coupons') > 1))
                                    ->disabled(fn (string $operation, Get $get) => $operation === 'create' && $get('number_of_coupons') > 1),

                                Forms\Components\Select::make('strategy')
                                    ->label('Strategy')
                                    ->options(
                                        collect(coupons()->getStrategies())
                                            ->mapWithKeys(fn ($strategy) => [$strategy->getName() => $strategy->getLabel()])
                                            ->toArray()
                                    )
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Components\Select $component) => $component
                                        ->getContainer()
                                        ->getComponent('payload')
                                        ->getChildComponentContainer()
                                        ->fill()),

                                Forms\Components\Group::make()
                                    ->key('payload')
                                    ->schema(fn (Get $get) => coupons()->getStrategyPayloadSchema($get('strategy')))
                                    ->visible(fn (Get $get) => coupons()->hasStrategyPayloadSchema($get('strategy')))
                                    ->statePath('payload')
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('active')
                                    ->label('Active')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('warning')
                                    ->onIcon('heroicon-o-check-circle')
                                    ->offIcon('heroicon-o-x-circle'),
                            ]),

                        Forms\Components\Section::make()
                            ->heading('Limits')
                            ->compact()
                            ->columns(2)
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Starts At')
                                    ->helperText('Leave empty for no start date'),

                                Forms\Components\DateTimePicker::make('expires_at')
                                    ->label('Expires At')
                                    ->helperText('Leave empty for no expiration')
                                    ->after('starts_at'),

                                Forms\Components\TextInput::make('usage_limit')
                                    ->label('Usage Limit')
                                    ->helperText('Leave empty for unlimited usage')
                                    ->suffix('uses')
                                    ->minValue(1)
                                    ->maxValue(1000000)
                                    ->numeric()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Forms\Components\Section::make()
                    ->heading('Multiple Creation')
                    ->description('Create multiple coupons at once.')
                    ->compact()
                    ->columnSpan(1)
                    ->visibleOn('create')
                    ->schema([
                        Forms\Components\TextInput::make('number_of_coupons')
                            ->label('Number of Coupons')
                            ->required()
                            ->numeric()
                            ->live()
                            ->suffix('coupons')
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $strategies = collect(coupons()->getStrategies())
            ->mapWithKeys(fn ($strategy) => [$strategy->getName() => $strategy->getLabel()])
            ->toArray();

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('strategy')
                    ->label('Strategy')
                    ->formatStateUsing(fn ($state) => $strategies[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts At')
                    ->date()
                    ->dateTimeTooltip()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->date()
                    ->dateTimeTooltip()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage_limit')
                    ->label('Usage Limit')
                    ->badge()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->usage_limit ?? 0)
                    ->formatStateUsing(function ($record, $state) {
                        $limit = $state > 0 ? Number::format($state) : '∞';

                        return Number::format($record->usages_count) . ' / ' . $limit;
                    }),

                Tables\Columns\ToggleColumn::make('active')
                    ->label('Active')
                    ->offIcon('heroicon-o-x-circle')
                    ->onIcon('heroicon-o-check-circle')
                    ->offColor('warning')
                    ->onColor('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->default(true),

                Tables\Filters\SelectFilter::make('strategy')
                    ->label('Strategy')
                    ->options($strategies),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'view' => Pages\ViewCoupon::route('/{record}'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('usages');
    }
}
