<?php

namespace App\Filament\Pages;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
