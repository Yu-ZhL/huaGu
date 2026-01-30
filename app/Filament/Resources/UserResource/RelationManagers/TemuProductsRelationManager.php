<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TemuProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'temuProducts';

    protected static ?string $title = 'TEMU采集产品';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['primarySource', 'sources1688']);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_id')
                    ->label('产品ID')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('产品标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sale_price')
                    ->label('售价')
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('weight')
                    ->label('重量(kg)')
                    ->numeric(),
                Forms\Components\TextInput::make('site_url')
                    ->label('产品链接')
                    ->url()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('封面')
                    ->size(60)
                    ->defaultImageUrl('/images/placeholder.png'),
                Tables\Columns\TextColumn::make('product_id')
                    ->label('产品ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('产品标题')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->title),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('售价')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('重量')
                    ->suffix(' kg')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_profit')
                    ->label('预估利润')
                    ->state(function ($record) {
                        $user = $record->user;
                        $primarySource = $record->primarySource;

                        if (!$primarySource || !$user) {
                            return null;
                        }

                        $salePrice = $record->sale_price ?? 0;
                        $cost = $primarySource->price ?? 0;
                        $weight = $record->weight ?? 0;
                        $freightPerKg = $user->freight_price_per_kg ?? 0;
                        $operationFee = $user->operation_fee ?? 0;

                        $freight = $weight * $freightPerKg;
                        $profit = $salePrice - $cost - $freight - $operationFee;

                        return $profit;
                    })
                    ->money('CNY')
                    ->color(fn($state) => $state === null ? 'gray' : ($state > 0 ? 'success' : 'danger'))
                    ->placeholder('未配置')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sources1688_count')
                    ->label('货源数')
                    ->counts('sources1688')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('primarySource.title')
                    ->label('主货源')
                    ->limit(30)
                    ->default('未选择'),
                Tables\Columns\TextColumn::make('collected_at')
                    ->label('采集时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_source')
                    ->label('已匹配货源')
                    ->query(fn(Builder $query) => $query->has('sources1688')),
                Tables\Filters\Filter::make('no_source')
                    ->label('未匹配货源')
                    ->query(fn(Builder $query) => $query->doesntHave('sources1688')),
            ])
            ->headerActions([
                // 禁止手动创建,只能通过插件采集
            ])
            ->actions([
                Tables\Actions\Action::make('viewSources')
                    ->label('查看货源')
                    ->icon('heroicon-o-link')
                    ->url(fn($record) => route('filament.admin.resources.temu-collected-products.edit', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('collected_at', 'desc');
    }
}
