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
                Tables\Columns\TextColumn::make('label')
                    ->label('配置项名称')
                    ->description(fn($record) => $record->key)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->icon(fn($state) => match ($state) {
                        'text' => 'heroicon-m-document-text',
                        'integer' => 'heroicon-m-hashtag',
                        'boolean' => 'heroicon-m-check-circle',
                        'json' => 'heroicon-m-code-bracket',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->color(fn($state) => match ($state) {
                        'text' => 'gray',
                        'integer' => 'info',
                        'boolean' => 'success',
                        'json' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'text' => '文本',
                        'integer' => '数字',
                        'boolean' => '布尔',
                        'json' => '配置集',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->label('配置预览')
                    ->limit(40)
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->key === 'customer_service' ? '点击编辑查看详情' : $state
                    )
                    ->color('gray'),

                Tables\Columns\IconColumn::make('status')
                    ->label('启用状态')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('最后更新')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultPaginationPageOption(20)
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型过滤')
                    ->options([
                        'text' => '文本',
                        'integer' => '数字',
                        'boolean' => '布尔',
                        'json' => '配置集',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('3xl')
                    ->button()
                    ->label('管理'),
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
