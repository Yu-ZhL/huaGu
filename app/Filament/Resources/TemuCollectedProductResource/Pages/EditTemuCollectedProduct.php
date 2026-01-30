<?php

namespace App\Filament\Resources\TemuCollectedProductResource\Pages;

use App\Filament\Resources\TemuCollectedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTemuCollectedProduct extends EditRecord
{
    protected static string $resource = TemuCollectedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
