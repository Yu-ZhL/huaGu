<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VipPlanResource\Pages;
use App\Models\VipPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VipPlanResource extends Resource
{
    protected static ?string $model = VipPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'VIP套餐管理';

    protected static ?string $modelLabel = 'VIP套餐';

    protected static ?string $navigationGroup = 'VIP系统';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('套餐名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->label('原价')
                    ->required()
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('final_price')
                    ->label('实际售价')
                    ->required()
                    ->numeric()
                    ->prefix('¥'),
                Forms\Components\TextInput::make('ai_points')
                    ->label('AI点数额度')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('duration_days')
                    ->label('有效期（天）')
                    ->required()
                    ->numeric()
                    ->default(30),
                Forms\Components\KeyValue::make('features')
                    ->label('功能列表')
                    ->keyLabel('序号')
                    ->valueLabel('功能描述'),
                Forms\Components\Toggle::make('is_recommended')
                    ->label('推荐套餐')
                    ->default(false),
                Forms\Components\TextInput::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('status')
                    ->label('状态')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('套餐名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('售价')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ai_points')
                    ->label('AI点数')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_days')
                    ->label('有效期')
                    ->suffix(' 天')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_recommended')
                    ->label('推荐')
                    ->boolean(),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
            ])
            ->filters([
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
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVipPlans::route('/'),
        ];
    }
}
