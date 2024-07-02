<?php

namespace App;

enum Vat: string
{
    case VAT_NA = '';
    case VAT_0 = '0';
    case VAT_12 = '12';
    case VAT_21 = '21';

    public static function asOptions(): array
    {
        return [
            self::VAT_NA->value => 'n/a',
            self::VAT_0->value => '0%',
            self::VAT_12->value => '12%',
            self::VAT_21->value => '21%',
        ];
    }
}
