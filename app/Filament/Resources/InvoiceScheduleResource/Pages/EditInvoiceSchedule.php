<?php

namespace App\Filament\Resources\InvoiceScheduleResource\Pages;

use App\Filament\Resources\InvoiceScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceSchedule extends EditRecord
{
    protected static string $resource = InvoiceScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
