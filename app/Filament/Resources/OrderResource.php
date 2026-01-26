<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = '订单管理';

    protected static ?string $modelLabel = '订单';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_no')
                    ->label('订单号')
                    ->disabled(),
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'phone')
                    ->searchable()
                    ->disabled(),
                Forms\Components\Select::make('vip_plan_id')
                    ->label('VIP套餐')
                    ->relationship('vipPlan', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('final_price')
                    ->label('实付金额')
                    ->prefix('¥')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->label('订单状态')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('支付时间')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_no')
                    ->label('订单号')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('用户手机号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan_name')
                    ->label('套餐名称'),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('实付金额')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('状态')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                        'secondary' => 'expired',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending' => '待支付',
                        'paid' => '已支付',
                        'cancelled' => '已取消',
                        'expired' => '已过期',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待支付',
                        'paid' => '已支付',
                        'cancelled' => '已取消',
                        'expired' => '已过期',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
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
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
