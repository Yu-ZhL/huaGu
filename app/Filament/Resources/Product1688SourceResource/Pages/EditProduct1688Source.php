<?php

namespace App\Filament\Resources\Product1688SourceResource\Pages;

use App\Filament\Resources\Product1688SourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct1688Source extends EditRecord
{
    protected static string $resource = Product1688SourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
