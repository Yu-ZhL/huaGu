<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AiPointRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'aiPointRecords';

    protected static ?string $title = 'AI点数日志';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('info')
                    ->label('')
                    ->content('AI点数记录仅供查看，不可编辑'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('points')
                    ->label('点数变动')
                    ->badge()
                    ->color(fn($record) => $record->points > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => ($state > 0 ? '+' : '') . $state),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('变动后余额')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'vip_purchase' => 'VIP购买',
                            'register_gift' => '注册赠送',
                            'consumption' => '消费扣除',
                            'admin_adjust' => '管理员调整',
                            default => $state,
                        };
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'vip_purchase' => 'success',
                            'register_gift' => 'info',
                            'consumption' => 'warning',
                            'admin_adjust' => 'primary',
                            default => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('说明')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->description)
                    ->searchable(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('过期时间')
                    ->dateTime()
                    ->placeholder('永久有效')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('记录时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型')
                    ->options([
                        'vip_purchase' => 'VIP购买',
                        'register_gift' => '注册赠送',
                        'consumption' => '消费扣除',
                        'admin_adjust' => '管理员调整',
                    ]),
                Tables\Filters\Filter::make('points_increase')
                    ->label('仅增加')
                    ->query(fn(Builder $query) => $query->where('points', '>', 0)),
                Tables\Filters\Filter::make('points_decrease')
                    ->label('仅扣除')
                    ->query(fn(Builder $query) => $query->where('points', '<', 0)),
            ])
            ->headerActions([
                // 只读,不允许创建
            ])
            ->actions([
                // 只读,不允许编辑删除
            ])
            ->bulkActions([
                // 不允许批量操作
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }
}
