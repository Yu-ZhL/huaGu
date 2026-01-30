<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemuCollectedProductResource\Pages;
use App\Filament\Resources\TemuCollectedProductResource\RelationManagers;
use App\Models\TemuCollectedProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TemuCollectedProductResource extends Resource
{
    protected static ?string $model = TemuCollectedProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'TEMU采集产品';

    protected static ?string $modelLabel = 'TEMU产品';

    protected static ?string $navigationGroup = '数据管理';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('所属用户')
                            ->relationship('user', 'phone')
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->phone ?: $record->email ?: "用户#{$record->id}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('product_id')
                            ->label('产品ID')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('platform')
                            ->label('平台')
                            ->default('temu')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('产品详情')
                    ->schema([
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('封面图')
                            ->image()
                            ->directory('products')
                            ->visibility('public'),
                        Forms\Components\TextInput::make('title')
                            ->label('产品标题')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('site_url')
                            ->label('产品链接')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('sale_price')
                            ->label('售价')
                            ->numeric()
                            ->prefix('¥')
                            ->step(0.01),
                        Forms\Components\TextInput::make('weight')
                            ->label('重量(kg)')
                            ->numeric()
                            ->step(0.001),
                        Forms\Components\TextInput::make('brand')
                            ->label('品牌')
                            ->maxLength(100),
                        Forms\Components\DateTimePicker::make('collected_at')
                            ->label('采集时间')
                            ->default(now()),
                    ])->columns(3),

                Forms\Components\Section::make('原始数据')
                    ->schema([
                        Forms\Components\Textarea::make('product_data')
                            ->label('产品数据(JSON)')
                            ->rows(10)
                            ->columnSpanFull()
                            ->disabled(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('封面')
                    ->size(60)
                    ->defaultImageUrl('/images/placeholder.png'),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('用户')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_id')
                    ->label('产品ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('产品标题')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn($record) => $record->title),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('售价')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('重量')
                    ->suffix(' kg')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sources1688_count')
                    ->label('货源数')
                    ->counts('sources1688')
                    ->badge()
                    ->color('success'),
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
                    ->placeholder('未配置'),
                Tables\Columns\TextColumn::make('collected_at')
                    ->label('采集时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('用户')
                    ->options(function () {
                        return \App\Models\User::query()
                            ->get()
                            ->mapWithKeys(fn($user) => [
                                $user->id => $user->phone ?: $user->email ?: "用户#{$user->id}"
                            ]);
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('has_source')
                    ->label('已匹配货源')
                    ->query(fn(Builder $query) => $query->has('sources1688')),
                Tables\Filters\Filter::make('no_source')
                    ->label('未匹配货源')
                    ->query(fn(Builder $query) => $query->doesntHave('sources1688')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('collected_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemuCollectedProducts::route('/'),
            'create' => Pages\CreateTemuCollectedProduct::route('/create'),
            'edit' => Pages\EditTemuCollectedProduct::route('/{record}/edit'),
        ];
    }
}
