<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;

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
                Forms\Components\Section::make('基本信息')
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
                    ])->columns(3),

                Forms\Components\Section::make('配置内容')
                    ->schema([
                        // 专属客服配置 (JSON 类型且 Key 为 customer_service)
                        Forms\Components\Group::make([
                            Forms\Components\FileUpload::make('value.qr_code')
                                ->label('专属客服二维码')
                                ->image()
                                ->directory('settings')
                                ->previewable(true)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('value.text')
                                ->label('底部文字信息')
                                ->placeholder('如：扫码添加专属客服')
                                ->columnSpanFull(),
                        ])
                            ->hidden(fn(Get $get) => $get('key') !== 'customer_service' || $get('type') !== 'json')
                            ->statePath('value'),

                        // 使用教程配置 (预览视频)
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('value')
                                ->label('视频路径')
                                ->helperText('默认：video/flyingCat_compressed.mp4')
                                ->required(),
                            Forms\Components\Placeholder::make('video_preview')
                                ->label('视频预览')
                                ->content(fn($record) => $record && $record->key === 'usage_tutorial' ? new HtmlString('
                                    <video width="100%" max-width="400" controls style="border-radius: 8px; border: 1px solid #ddd;">
                                        <source src="' . asset($record->value) . '" type="video/mp4">
                                        您的浏览器不支持 video 标签。
                                    </video>
                                ') : '暂无预览'),
                        ])->hidden(fn(Get $get) => $get('key') !== 'usage_tutorial'),

                        // 普通文本/整数等
                        Forms\Components\TextInput::make('value')
                            ->label('配置值')
                            ->required()
                            ->hidden(fn(Get $get) => in_array($get('key'), ['customer_service', 'usage_tutorial'])),

                        Forms\Components\Textarea::make('description')
                            ->label('描述')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Toggle::make('status')
                    ->label('状态')
                    ->default(true),

                Forms\Components\Placeholder::make('help')
                    ->label('使用说明')
                    ->content('在代码中使用 SiteSetting::get(\'key\') 获取。复杂配置将返回数组格式。')
                    ->columnSpanFull(),
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
        ];
    }
}
