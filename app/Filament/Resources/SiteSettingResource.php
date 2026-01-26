<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = '网站配置';

    protected static ?string $modelLabel = '配置';

    protected static ?string $navigationGroup = '系统设置';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('配置键')
                    ->required()
                    ->disabled(fn($record) => $record !== null)
                    ->maxLength(100),
                Forms\Components\TextInput::make('label')
                    ->label('显示名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('类型')
                    ->options([
                        'text' => '文本',
                        'integer' => '整数',
                        'boolean' => '布尔值',
                        'json' => 'JSON',
                    ])
                    ->required()
                    ->default('text'),
                Forms\Components\TextInput::make('value')
                    ->label('配置值')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('配置键')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('显示名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('配置值')
                    ->limit(50),
                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'text' => '文本',
                        'integer' => '整数',
                        'boolean' => '布尔值',
                        'json' => 'JSON',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('2xl'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}
