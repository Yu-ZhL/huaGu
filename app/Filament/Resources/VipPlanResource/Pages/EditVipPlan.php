<?php

namespace App\Filament\Resources\VipPlanResource\Pages;

use App\Filament\Resources\VipPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVipPlan extends EditRecord
{
    protected static string $resource = VipPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
