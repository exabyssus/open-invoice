<?php

use App\Models\InvoiceSchedule;
use Carbon\Carbon;

function nextRun(string $frequency, int $day, ?int $month, string $from): string
{
    return (new InvoiceSchedule(['frequency' => $frequency, 'day' => $day, 'month' => $month]))
        ->occurrenceOnOrAfter(Carbon::parse($from))->toDateString();
}

it('finds the next monthly date', function () {
    expect(nextRun('MONTHLY', 15, null, '2026-10-10'))->toBe('2026-10-15')
        ->and(nextRun('MONTHLY', 15, null, '2026-10-15'))->toBe('2026-10-15') // today counts
        ->and(nextRun('MONTHLY', 15, null, '2026-10-16'))->toBe('2026-11-15')
        ->and(nextRun('MONTHLY', 5, null, '2026-12-20'))->toBe('2027-01-05');
});

it('uses the last day of a shorter month', function () {
    expect(nextRun('MONTHLY', 31, null, '2026-02-01'))->toBe('2026-02-28')
        ->and(nextRun('MONTHLY', 31, null, '2026-03-01'))->toBe('2026-03-31')
        ->and(nextRun('MONTHLY', 31, null, '2026-04-01'))->toBe('2026-04-30')
        ->and(nextRun('MONTHLY', 31, null, '2026-04-30'))->toBe('2026-04-30')
        ->and(nextRun('MONTHLY', 30, null, '2028-02-01'))->toBe('2028-02-29'); // leap year
});

it('finds the next yearly date', function () {
    expect(nextRun('YEARLY', 1, 3, '2026-01-10'))->toBe('2026-03-01')
        ->and(nextRun('YEARLY', 1, 3, '2026-03-01'))->toBe('2026-03-01')
        ->and(nextRun('YEARLY', 1, 3, '2026-03-02'))->toBe('2027-03-01')
        ->and(nextRun('YEARLY', 29, 2, '2027-01-01'))->toBe('2027-02-28')
        ->and(nextRun('YEARLY', 29, 2, '2028-01-01'))->toBe('2028-02-29')
        ->and(nextRun('YEARLY', 29, 2, '2028-03-01'))->toBe('2029-02-28');
});
