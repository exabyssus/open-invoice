<?php

namespace App\Support;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;

class Money
{
    public static function defaultFormat(\Money\Money $money): string
    {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('lv_LV', \NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        return $moneyFormatter->format($money);
    }
}
