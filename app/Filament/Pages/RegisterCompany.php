<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\NumberStyles;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterCompany extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Create Company';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('title')->required(),
                TextInput::make('registration_number')->required(),
                TextInput::make('address')->required(),
            ]);
    }

    public static function canView(): bool
    {
        return true;
    }

    protected function handleRegistration(array $data): Company
    {
        $data['number_style'] = NumberStyles::NUMBER_STYLE_NONE->name;
        $data['owner_id'] = auth()->id();

        $company = Company::create($data);
        $company->users()->attach(auth()->user());

        return $company;
    }
}
