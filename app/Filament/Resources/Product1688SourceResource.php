<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Product1688SourceResource\Pages;
use App\Filament\Resources\Product1688SourceResource\RelationManagers;
use App\Models\Product1688Source;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Product1688SourceResource extends Resource
{
    protected static ?string $model = Product1688Source::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = '1688货源';

    protected static ?string $modelLabel = '1688货源';

    protected static ?string $navigationGroup = '数据管理';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('关联信息')
                    ->schema([
                        Forms\Components\Select::make('temu_product_id')
                            ->label('TEMU产品')
                            ->relationship('temuProduct', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('用户')
                            ->relationship('user', 'phone')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('设为主货源')
                            ->default(false),
                    ])->columns(3),

                Forms\Components\Section::make('货源详情')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('商品图片')
                            ->image()
                            ->directory('sources')
                            ->visibility('public'),
                        Forms\Components\TextInput::make('title')
                            ->label('商品标题')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('url')
                            ->label('商品链接')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('price')
                            ->label('价格')
                            ->numeric()
                            ->prefix('¥')
                            ->step(0.01)
                            ->required(),
                        Forms\Components\Select::make('search_method')
                            ->label('搜索方式')
                            ->options([
                                'image' => '图片搜索',
                                'text' => '文字搜索',
                                'manual' => '手动添加',
                            ])
                            ->default('image'),
                        Forms\Components\TagsInput::make('tags')
                            ->label('标签')
                            ->placeholder('添加标签'),
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
                Tables\Columns\ImageColumn::make('image')
                    ->label('图片')
                    ->size(60)
                    ->defaultImageUrl('/images/placeholder.png'),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('用户')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('temuProduct.product_id')
                    ->label('TEMU产品ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('商品标题')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn($record) => $record->title),
                Tables\Columns\TextColumn::make('price')
                    ->label('价格')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit')
                    ->label('单件利润')
                    ->state(function ($record) {
                        $temuProduct = $record->temuProduct;
                        $user = $record->user;

                        if (!$temuProduct || !$user) {
                            return null;
                        }

                        $salePrice = $temuProduct->sale_price ?? 0;
                        $cost = $record->price ?? 0;
                        $weight = $temuProduct->weight ?? 0;
                        $freightPerKg = $user->freight_price_per_kg ?? 0;
                        $operationFee = $user->operation_fee ?? 0;

                        $freight = $weight * $freightPerKg;
                        $profit = $salePrice - $cost - $freight - $operationFee;

                        return $profit;
                    })
                    ->money('CNY')
                    ->color(fn($state) => $state === null ? 'gray' : ($state > 0 ? 'success' : 'danger'))
                    ->placeholder('未配置'),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('主货源')
                    ->boolean(),
                Tables\Columns\TextColumn::make('search_method')
                    ->label('搜索方式')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'image' => '图片搜索',
                        'text' => '文字搜索',
                        'manual' => '手动添加',
                        default => $state,
                    })
                    ->color(fn($state) => match ($state) {
                        'image' => 'info',
                        'text' => 'warning',
                        'manual' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'phone')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('主货源')
                    ->boolean()
                    ->trueLabel('仅主货源')
                    ->falseLabel('非主货源'),
                Tables\Filters\SelectFilter::make('search_method')
                    ->label('搜索方式')
                    ->options([
                        'image' => '图片搜索',
                        'text' => '文字搜索',
                        'manual' => '手动添加',
                    ]),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListProduct1688Sources::route('/'),
            'create' => Pages\CreateProduct1688Source::route('/create'),
            'edit' => Pages\EditProduct1688Source::route('/{record}/edit'),
        ];
    }
}
