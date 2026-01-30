<?php

namespace App\Filament\Resources\Product1688SourceResource\Pages;

use App\Filament\Resources\Product1688SourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProduct1688Sources extends ListRecords
{
    protected static string $resource = Product1688SourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
