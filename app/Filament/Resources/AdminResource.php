<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Filament\Resources\AdminResource\RelationManagers;
use App\Models\Admin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('名称')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->required(), // 必须唯一，不然登录会冲突
                Forms\Components\TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->dehydrated(fn($state) => filled($state)) // 修改时，如果不填密码就不更新
                    ->required(fn(string $context): bool => $context === 'create'), // 创建的时候必填
                Forms\Components\FileUpload::make('avatar')
                    ->label('头像')
                    ->avatar(), // 直接用 Filament 自带的头像上传
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')->circular()->label('头像'),
                Tables\Columns\TextColumn::make('name')->searchable()->label('名称'),
                Tables\Columns\TextColumn::make('email')->searchable()->label('邮箱'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->label('创建时间'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
