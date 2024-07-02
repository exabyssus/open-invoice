<?php

namespace App\Services;

use Illuminate\Support\Str;

class GenerateTextualPrice
{
    public function __invoke(float $price): string
    {
        $major = (int)floor($price);
        $minor = (int)round(($price - $major) * 100);

        if ($major < 0 || $minor < 0) {
            return $price;
        }

        $formatter = new \NumberFormatter('lv_LV', \NumberFormatter::CURRENCY_CODE);
        $majorText = $formatter->format($major) . ' <i>euro</i>';

        $minorText = ' un ' . $minor . ' ' . ($minor === 1 ? 'cents' : 'centi');

        return Str::ucfirst($majorText) . $minorText;
    }
}
