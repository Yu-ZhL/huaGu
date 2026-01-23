<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?string $modelLabel = '用户';

    protected static ?string $pluralModelLabel = '用户';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('姓名/昵称'), // 给用户看的，写友好点
                Forms\Components\TextInput::make('email')
                    ->label('邮箱')
                    ->email(),
                Forms\Components\TextInput::make('phone')
                    ->label('手机号'),
                Forms\Components\TextInput::make('invitation_code')
                    ->label('邀请码'),
                Forms\Components\TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->dehydrated(fn($state) => filled($state)) // 只有填写了才更新密码，不填就不改
                    ->required(fn(string $context): bool => $context === 'create'), // 创建时必须填
                Forms\Components\TextInput::make('plaintext_password')
                    ->label('明文密码')
                    ->helperText('仅用于特殊需求，请谨慎修改'), // 提示一下运营人员
                Forms\Components\Select::make('vip_level_id')
                    ->label('VIP等级')
                    ->options([
                        0 => '普通用户',
                        1 => 'VIP 1',
                        2 => 'VIP 2',
                        // 后期如果VIP等级多了，得改成从数据库或者配置文件读取
                    ]),
                Forms\Components\DateTimePicker::make('vip_expired_at')
                    ->label('VIP过期时间'),
                Forms\Components\Toggle::make('status')
                    ->label('状态')
                    ->onColor('success')
                    ->offColor('danger')
                    ->inline(false)
                    ->default(true), // 默认启用
                Forms\Components\Textarea::make('remark')
                    ->label('备注')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->label('姓名'),
                Tables\Columns\TextColumn::make('email')->searchable()->label('邮箱'),
                Tables\Columns\TextColumn::make('phone')->searchable()->label('手机号'),
                Tables\Columns\TextColumn::make('vip_level_id')->sortable()->label('VIP等级'),
                Tables\Columns\IconColumn::make('status')->boolean()->label('状态'), // 勾或者叉，直观
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->label('创建时间'),
            ])
            ->filters([
                // 暂时还没加筛选，后面有需求再说
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
