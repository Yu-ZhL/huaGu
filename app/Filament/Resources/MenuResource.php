<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Filament\Resources\MenuResource\RelationManagers;
use App\Models\Menu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationLabel = '菜单管理';

    protected static ?string $modelLabel = '菜单';

    protected static ?string $pluralModelLabel = '菜单';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('parent_id')
                    ->label('父级菜单')
                    ->relationship('parent', 'title') // 关联自己，无限级菜单
                    ->searchable()
                    ->preload()
                    ->default(0), // 默认顶级
                Forms\Components\TextInput::make('title')
                    ->label('菜单名称')
                    ->required(), // 必填，不然不知道叫啥
                Forms\Components\TextInput::make('icon')
                    ->label('图标 (Class/Path)')
                    ->helperText('可以用 Heroicons 或者 FontAwesome 类名'),
                Forms\Components\TextInput::make('route')
                    ->label('路由名称')
                    ->helperText('对应 Laravel 路由的 name'),
                Forms\Components\TextInput::make('url')
                    ->label('URL链接')
                    ->helperText('如果是外链，直接填完整 http 地址'),
                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0), // 默认0，排在最前面
                Forms\Components\Toggle::make('is_active')
                    ->label('启用')
                    ->default(true),
                Forms\Components\Toggle::make('is_hidden')
                    ->label('隐藏')
                    ->helperText('隐藏菜单不会在侧边栏显示，但是可以直接访问')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->label('名称'),
                Tables\Columns\TextColumn::make('parent.title')->label('父级'), // 显示父级菜单名字
                Tables\Columns\TextColumn::make('route')->label('路由'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('启用'),
                Tables\Columns\TextColumn::make('sort')->sortable()->label('排序'), // 方便调整顺序
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->label('更新时间'),
            ])
            ->filters([
                // 暂时不需要太复杂的筛选
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
