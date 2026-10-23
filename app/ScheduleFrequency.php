<?php

namespace App;

enum ScheduleFrequency: string
{
    case MONTHLY = 'MONTHLY';
    case YEARLY = 'YEARLY';

    public static function asOptions(): array
    {
        return [
            self::MONTHLY->value => __('resources.schedule.monthly'),
            self::YEARLY->value => __('resources.schedule.yearly'),
        ];
    }
}
