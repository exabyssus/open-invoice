<?php

namespace App\Filament\Pages;
use App\NumberStyles;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class CompanyProfile extends EditTenantProfile
{
    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\InvoiceLinesRelationManager::class
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                TextInput::make('title')
                    ->columnSpanFull()
                    ->label(__('resources.company.title'))
                    ->required(),
                TextInput::make('registration_number')->label(__('resources.company.registration_number')),
                TextInput::make('vat_number')->label(__('resources.company.vat_number')),
                TextInput::make('address')
                    ->columnSpanFull()
                    ->label(__('resources.company.address')),
                Toggle::make('show_accounts')
                    ->inline(false)
                    ->columnSpanFull()
                    ->label(__('resources.company.show_accounts')),
                Group::make(function () {
                    return [
                        TextInput::make('bank_name')->label(__('resources.company.bank_name')),
                        TextInput::make('bank_swift')->label(__('resources.company.bank_swift')),
                        TextInput::make('bank_iban')->label(__('resources.company.bank_iban')),
                    ];
                })
                    ->columnSpanFull()
                    ->columns(2),
                Group::make(function () {
                    return [
                        TextInput::make('number_prefix')->label(__('resources.company.number_prefix')),
                        Select::make('number_style')
                            ->label(__('resources.company.number_style'))
                            ->options([
                                NumberStyles::NUMBER_STYLE_YEARLY->name => __('resources.company.number_style_yearly'),
                                NumberStyles::NUMBER_STYLE_ALL_TIME->name => __('resources.company.number_style_all_time'),
                                NumberStyles::NUMBER_STYLE_DATE->name => __('resources.company.number_style_date'),
                                NumberStyles::NUMBER_STYLE_NONE->name => __('resources.company.number_style_none'),
                            ]),
                        TextInput::make('number_suffix')->label(__('resources.company.number_suffix')),
                    ];
                })
                    ->columnSpanFull()
                    ->columns(3),
                FileUpload::make('logo')
                    ->label(__('resources.company.logo'))
                    ->disk('private')
                    ->previewable(false)
                    ->image(),
            ]);
    }

    public static function getLabel(): string
    {
        return __('pages.action_edit', ['name' => Filament::getTenant()->title]);
    }
}
