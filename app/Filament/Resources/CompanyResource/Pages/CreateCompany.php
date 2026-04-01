<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected function afterCreate(): void
    {
        $company = $this->record;

        if ($company->owner_id && ! $company->users()->where('users.id', $company->owner_id)->exists()) {
            $company->users()->attach($company->owner_id);
        }
    }
}
