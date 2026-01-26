<?php

namespace App\Filament\Resources\VipPlanResource\Pages;

use App\Filament\Resources\VipPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVipPlans extends ListRecords
{
    protected static string $resource = VipPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalWidth('3xl'),
        ];
    }
}
