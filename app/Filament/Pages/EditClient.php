<?php

namespace App\Filament\Pages;
use App\Filament\Resources\ClientResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    /**
     * @return string|null
     */
    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public function getHeading(): string|Htmlable
    {
        return __('pages.action_edit', ['name' => $this->getRecord()->title]);
    }
}
