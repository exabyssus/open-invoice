<?php

namespace App;

enum Units
{
    case NO_UNIT;
    case SERVICE;
    case HOUR;
    case YEAR;
    case MONTH;
    case QUARTER;
    case UNIT;
    case KILO;
    case METER;

    public static function asOptions(): array
    {
        return collect([
            self::NO_UNIT,
            self::SERVICE,
            self::UNIT,
            self::HOUR,
            self::MONTH,
            self::QUARTER,
            self::YEAR,
            self::KILO,
            self::METER,
        ])->mapWithKeys(function (Units $value, $key) {
            return [$value->name => __('units.'. strtolower($value->name))];
        })->toArray();
    }
}
