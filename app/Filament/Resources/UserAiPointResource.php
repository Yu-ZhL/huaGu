<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserAiPointResource\Pages;
use App\Models\UserAiPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserAiPointResource extends Resource
{
    protected static ?string $model = UserAiPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'AI点数日志';

    protected static ?string $modelLabel = 'AI点数记录';

    protected static ?string $navigationGroup = 'VIP系统';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'phone')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('类型')
                    ->options([
                        UserAiPoint::TYPE_PURCHASE => '购买获得',
                        UserAiPoint::TYPE_GIFT => '赠送获得',
                        UserAiPoint::TYPE_CONSUME => '消费扣除',
                        UserAiPoint::TYPE_ADMIN_ADJUST => '管理员调整',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('points')
                    ->label('点数')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('source_id')
                    ->label('来源ID')
                    ->numeric(),
                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->rows(2),
                Forms\Components\DateTimePicker::make('expired_at')
                    ->label('过期时间'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('用户手机号')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        UserAiPoint::TYPE_PURCHASE => 'success',
                        UserAiPoint::TYPE_GIFT => 'info',
                        UserAiPoint::TYPE_CONSUME => 'danger',
                        UserAiPoint::TYPE_ADMIN_ADJUST => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        UserAiPoint::TYPE_PURCHASE => '购买获得',
                        UserAiPoint::TYPE_GIFT => '赠送获得',
                        UserAiPoint::TYPE_CONSUME => '消费扣除',
                        UserAiPoint::TYPE_ADMIN_ADJUST => '管理员调整',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('points')
                    ->label('点数')
                    ->numeric()
                    ->sortable()
                    ->color(fn($record) => $record->points > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('description')
                    ->label('描述')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('过期时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型')
                    ->options([
                        UserAiPoint::TYPE_PURCHASE => '购买获得',
                        UserAiPoint::TYPE_GIFT => '赠送获得',
                        UserAiPoint::TYPE_CONSUME => '消费扣除',
                        UserAiPoint::TYPE_ADMIN_ADJUST => '管理员调整',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'phone')
                    ->searchable()
                    ->multiple(),
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
            'index' => Pages\ListUserAiPoints::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
