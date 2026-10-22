<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\NumberStyles;
use Carbon\Carbon;

class GenerateInvoiceNumber
{
    public const NUMBER_STYLE_DATE_FORMAT = 'Y.m.d';

    /** Words in a company's prefix/suffix that are replaced when the number is generated. */
    public const CUSTOMER_CODE = 'CUSTOMER_CODE';

    /** CUSTOMER_CODE is replaced with the client's code (empty until a client is chosen); {{CUSTOMER_CODE}} works too. */
    public function __invoke(Company $company, ?Client $client = null): string
    {
        $number = match ($company->number_style) {
            NumberStyles::NUMBER_STYLE_YEARLY => $this->yearly($company),
            NumberStyles::NUMBER_STYLE_ALL_TIME => $this->allTime($company),
            NumberStyles::NUMBER_STYLE_DATE => $this->date($company),
            NumberStyles::NUMBER_STYLE_DATE_SHORT => $this->dateShort($company),
            default => '',
        };

        return str_replace(['{{'.self::CUSTOMER_CODE.'}}', self::CUSTOMER_CODE], $client?->code ?? '', $number);
    }

    public static function usesCustomerCode(Company $company): bool
    {
        return $company->number_style !== NumberStyles::NUMBER_STYLE_NONE
            && str_contains($company->number_prefix.$company->number_suffix, self::CUSTOMER_CODE);
    }

    private function yearly(Company $company): string
    {
        $number = $company
                ->invoices()
                ->withTrashed()
                ->where('created_at', '>', Carbon::now()->startOfYear())
                ->count() + 1;

        return sprintf('%s%s%s', $company->number_prefix, $number, $company->number_suffix);
    }

    private function allTime(Company $company): string
    {
        $number = $company->invoices()->withTrashed()->count() + 1;
        return sprintf('%s%s%s', $company->number_prefix, $number, $company->number_suffix);
    }

    // DDMMYY- and the rest (a client code, say) is typed by hand
    private function dateShort(Company $company): string
    {
        return sprintf('%s%s-%s', $company->number_prefix, Carbon::now()->format('dmy'), $company->number_suffix);
    }

    private function date(Company $company): string
    {
        $date = Carbon::now()->format(self::NUMBER_STYLE_DATE_FORMAT);
        return sprintf('%s%s%s', $company->number_prefix, $date, $company->number_suffix);
    }
}
