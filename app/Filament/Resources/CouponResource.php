<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = '优惠码管理';

    protected static ?string $modelLabel = '优惠码';

    protected static ?string $navigationGroup = 'VIP系统';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('优惠码')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('用户输入的优惠码'),
                        Forms\Components\TextInput::make('name')
                            ->label('名称')
                            ->required()
                            ->maxLength(255)
                            ->helperText('用于管理识别'),
                    ])->columns(2),

                Forms\Components\Section::make('折扣设置')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('类型')
                            ->options([
                                'fixed' => '固定金额',
                                'percent' => '百分比折扣',
                            ])
                            ->required()
                            ->default('fixed')
                            ->live(),
                        Forms\Components\TextInput::make('discount_value')
                            ->label('折扣值')
                            ->required()
                            ->numeric()
                            ->suffix(fn($get) => $get('type') === 'percent' ? '%' : '元'),
                        Forms\Components\TextInput::make('min_amount')
                            ->label('最低消费金额')
                            ->numeric()
                            ->prefix('¥')
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('使用限制')
                    ->schema([
                        Forms\Components\TextInput::make('total_count')
                            ->label('发行数量')
                            ->numeric()
                            ->default(0)
                            ->helperText('0表示不限制'),
                        Forms\Components\TextInput::make('used_count')
                            ->label('已使用次数')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('开始时间'),
                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('结束时间'),
                    ])->columns(2),

                Forms\Components\Section::make('状态')
                    ->schema([
                        Forms\Components\Toggle::make('status')
                            ->label('启用状态')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('优惠码')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'fixed' ? '固定金额' : '百分比折扣'),
                Tables\Columns\TextColumn::make('discount_value')
                    ->label('折扣值')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->type === 'percent' ? $record->discount_value . '%' : '¥' . $record->discount_value
                    ),
                Tables\Columns\TextColumn::make('usage')
                    ->label('使用情况')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->used_count . ' / ' . ($record->total_count ?: '∞')
                    ),
                Tables\Columns\TextColumn::make('min_amount')
                    ->label('最低消费')
                    ->money('CNY'),
                Tables\Columns\TextColumn::make('end_at')
                    ->label('结束时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型')
                    ->options([
                        'fixed' => '固定金额',
                        'percent' => '百分比折扣',
                    ]),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('状态')
                    ->boolean()
                    ->trueLabel('启用')
                    ->falseLabel('禁用'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('3xl'),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
        ];
    }
}
