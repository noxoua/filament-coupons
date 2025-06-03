<?php

declare(strict_types=1);

namespace Noxo\FilamentCoupons\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Resources\CouponResource\Pages;
use Noxo\FilamentCoupons\Resources\CouponResource\RelationManagers;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return __('filament-coupons::filament-coupons.resource.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-coupons::filament-coupons.resource.plural_title');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Section::make()
                            ->heading(__('filament-coupons::filament-coupons.resource.form.fields.code'))
                            ->compact()
                            ->columns(2)
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label(__('filament-coupons::filament-coupons.resource.form.fields.code'))
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(20)
                                    // TODO: ->required() not working as expected with disabled condition
                                    ->required(fn (string $operation, Get $get) => ! ($operation === 'create' && $get('number_of_coupons') > 1))
                                    ->disabled(fn (string $operation, Get $get) => $operation === 'create' && $get('number_of_coupons') > 1),

                                Forms\Components\Select::make('strategy')
                                    ->label(__('filament-coupons::filament-coupons.resource.form.fields.strategy'))
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
                                        ?->getChildComponentContainer()
                                        ->fill()),

                                Forms\Components\Group::make()
                                    ->key('payload')
                                    ->schema(fn (Get $get) => coupons()->getStrategyPayloadSchema($get('strategy')))
                                    ->visible(fn (Get $get) => coupons()->hasStrategyPayloadSchema($get('strategy')))
                                    ->statePath('payload')
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('active')
                                    ->label(__('filament-coupons::filament-coupons.resource.form.fields.active'))
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('warning')
                                    ->onIcon('heroicon-o-check-circle')
                                    ->offIcon('heroicon-o-x-circle'),
                            ]),

                        Forms\Components\Section::make()
                            ->heading(__('filament-coupons::filament-coupons.resource.form.limits'))
                            ->compact()
                            ->columns(2)
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label(__('filament-coupons::filament-coupons.resource.form.fields.starts_at.label'))
                                    ->helperText(__(
                                        'filament-coupons::filament-coupons.resource.form.fields.starts_at.help'
                                    )),

                                Forms\Components\DateTimePicker::make('expires_at')
                                    ->label(__('filament-coupons::filament-coupons.resource.form.fields.expires_at.label'))
                                    ->helperText(__('filament-coupons::filament-coupons.resource.form.fields.expires_at.help'))
                                    ->after('starts_at'),

                                Forms\Components\TextInput::make('usage_limit')
                                    ->label(__('filament-coupons::filament-coupons.resource.form.fields.usage_limit.label'))
                                    ->helperText(__(
                                        'filament-coupons::filament-coupons.resource.form.fields.usage_limit.help'
                                    ))
                                    ->suffix('uses')
                                    ->minValue(1)
                                    ->maxValue(1000000)
                                    ->numeric()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Forms\Components\Section::make()
                    ->heading(__('filament-coupons::filament-coupons.resource.form.multiple_creation.heading'))
                    ->description(__('filament-coupons::filament-coupons.resource.form.multiple_creation.description'))
                    ->compact()
                    ->columnSpan(1)
                    ->visibleOn('create')
                    ->schema([
                        Forms\Components\TextInput::make('number_of_coupons')
                            ->label(__('filament-coupons::filament-coupons.resource.form.fields.number_of_coupons'))
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
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.code'))
                    ->badge()
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('strategy')
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.strategy'))
                    ->formatStateUsing(fn ($state) => $strategies[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.starts_at'))
                    ->date()
                    ->dateTimeTooltip()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.expires_at'))
                    ->date()
                    ->dateTimeTooltip()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage_limit')
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.usage_limit'))
                    ->badge()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->usage_limit ?? 0)
                    ->formatStateUsing(function ($record, $state) {
                        $limit = $state > 0 ? Number::format($state) : '∞';

                        return Number::format($record->usages_count).' / '.$limit;
                    }),

                Tables\Columns\ToggleColumn::make('active')
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.active'))
                    ->offIcon('heroicon-o-x-circle')
                    ->onIcon('heroicon-o-check-circle')
                    ->offColor('warning')
                    ->onColor('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-coupons::filament-coupons.resource.table.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('filament-coupons::filament-coupons.resource.table.filters.active'))
                    ->placeholder(__('filament-coupons::filament-coupons.resource.table.filters.all'))
                    ->trueLabel(__('filament-coupons::filament-coupons.resource.table.filters.active'))
                    ->falseLabel(__('filament-coupons::filament-coupons.resource.table.filters.inactive'))
                    ->default(true),

                Tables\Filters\SelectFilter::make('strategy')
                    ->label(__('filament-coupons::filament-coupons.resource.table.filters.strategy'))
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

    public function getTitle(): string|Htmlable
    {
        return __('filament-coupons::filament-coupons.resource.title');
    }
}
