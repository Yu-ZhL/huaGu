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

    protected static ?string $navigationGroup = '用户管理';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('姓名/昵称'),
                        Forms\Components\TextInput::make('email')
                            ->label('邮箱')
                            ->email(),
                        Forms\Components\TextInput::make('phone')
                            ->label('手机号')
                            ->required(),
                        Forms\Components\TextInput::make('phone_area_code')
                            ->label('区号')
                            ->default('86'),
                        Forms\Components\TextInput::make('invitation_code')
                            ->label('邀请码'),
                    ])->columns(2),

                Forms\Components\Section::make('密码设置')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('密码')
                            ->password()
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create'),
                        Forms\Components\TextInput::make('plaintext_password')
                            ->label('明文密码')
                            ->helperText('仅用于特殊需求，请谨慎修改'),
                    ])->columns(2),

                Forms\Components\Section::make('VIP信息')
                    ->schema([
                        Forms\Components\Select::make('vip_level_id')
                            ->label('VIP套餐')
                            ->relationship('vipPlan', 'name')
                            ->searchable()
                            ->preload()
                            ->default(0),
                        Forms\Components\DateTimePicker::make('vip_expired_at')
                            ->label('VIP过期时间'),
                        Forms\Components\TextInput::make('ai_points')
                            ->label('AI点数余额')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('账号状态')
                    ->schema([
                        Forms\Components\Toggle::make('status')
                            ->label('账号状态')
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false)
                            ->default(true),
                        Forms\Components\Textarea::make('remark')
                            ->label('备注')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('手机号')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vipPlan.name')
                    ->label('VIP套餐')
                    ->default('普通用户')
                    ->badge()
                    ->color(fn($record) => $record->vip_level_id > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('vip_expired_at')
                    ->label('VIP过期时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ai_points')
                    ->label('AI点数')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('注册时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vip_level_id')
                    ->label('VIP等级')
                    ->relationship('vipPlan', 'name')
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('账号状态')
                    ->boolean()
                    ->trueLabel('正常')
                    ->falseLabel('禁用'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('3xl'),
                Tables\Actions\Action::make('adjustPoints')
                    ->label('调整点数')
                    ->icon('heroicon-o-currency-dollar')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label('调整点数')
                            ->helperText('正数为增加，负数为扣除')
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('调整说明')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $points = intval($data['points']);
                        if ($points > 0) {
                            $record->addAiPoints($points, \App\Models\UserAiPoint::TYPE_ADMIN_ADJUST, $data['description']);
                        } else {
                            $record->deductAiPoints(abs($points), \App\Models\UserAiPoint::TYPE_ADMIN_ADJUST, $data['description']);
                        }
                    })
                    ->successNotificationTitle('点数调整成功'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
