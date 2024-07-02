<?php

namespace App\Services;

use App\Models\Company;
use App\NumberStyles;
use Carbon\Carbon;

class GenerateInvoiceNumber
{
    public const NUMBER_STYLE_DATE_FORMAT = 'Y.m.d';

    public function __invoke(Company $company): string
    {
        return match ($company->number_style) {
            NumberStyles::NUMBER_STYLE_YEARLY => $this->yearly($company),
            NumberStyles::NUMBER_STYLE_ALL_TIME => $this->allTime($company),
            NumberStyles::NUMBER_STYLE_DATE => $this->date($company),
            default => '',
        };
    }

    private function yearly(Company $company): string
    {
        $number = $company
                ->invoices()
                ->where('created_at', '>', Carbon::now()->startOfYear())
                ->count() + 1;

        return sprintf('%s%s%s', $company->number_prefix, $number, $company->number_suffix);
    }

    private function allTime(Company $company): string
    {
        $number = $company->invoices()->count() + 1;
        return sprintf('%s%s%s', $company->number_prefix, $number, $company->number_suffix);
    }

    private function date(Company $company): string
    {
        $date = Carbon::now()->format(self::NUMBER_STYLE_DATE_FORMAT);
        return sprintf('%s%s%s', $company->number_prefix, $date, $company->number_suffix);
    }
}
