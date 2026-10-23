<?php

namespace App\Models;

use App\ScheduleFrequency;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A template that makes a regular invoice every month or year (php artisan invoices:run-schedules, daily).
 *
 * @property ScheduleFrequency frequency
 * @property ?int month
 * @property int day
 * @property array lines
 * @property Carbon next_run_on
 */
class InvoiceSchedule extends Model
{
    protected $fillable = [
        'client_id',
        'frequency',
        'month',
        'day',
        'due_days',
        'auto_send',
        'is_active',
        'comment',
        'lines',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => ScheduleFrequency::class,
            'next_run_on' => 'date',
            'last_run_at' => 'datetime',
            'lines' => 'array',
            'auto_send' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $schedule) {
            $schedule->created_by ??= auth()->id();
            // the next run follows the settings whenever they change
            if (! $schedule->next_run_on || $schedule->isDirty(['frequency', 'month', 'day'])) {
                $schedule->next_run_on = $schedule->occurrenceOnOrAfter(Carbon::today());
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** First day on or after $from that this schedule falls on; day 31 in a shorter month means that month's last day. */
    public function occurrenceOnOrAfter(CarbonInterface $from): Carbon
    {
        $from = Carbon::instance($from)->startOfDay();
        $at = fn (int $year, int $month) => ($first = Carbon::create($year, $month, 1))->setDay(min($this->day, $first->daysInMonth));

        if ($this->frequency === ScheduleFrequency::MONTHLY) {
            $date = $at($from->year, $from->month);
            $next = $from->copy()->startOfMonth()->addMonth();

            return $date->gte($from) ? $date : $at($next->year, $next->month);
        }

        $date = $at($from->year, $this->month);

        return $date->gte($from) ? $date : $at($from->year + 1, $this->month);
    }
}
