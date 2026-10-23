<?php

namespace App\Console\Commands;

use App\Models\InvoiceSchedule;
use App\Services\RunInvoiceSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class RunInvoiceSchedules extends Command
{
    protected $signature = 'invoices:run-schedules';

    protected $description = 'Make (and mail) the invoices of every active schedule that is due';

    public function handle(RunInvoiceSchedule $run): int
    {
        $failed = 0;

        // due = next_run_on today or earlier, so a missed day is made up on the next run (once, not once per missed day)
        InvoiceSchedule::query()->where('is_active', true)->whereDate('next_run_on', '<=', Carbon::today())->with('company')->each(
            function (InvoiceSchedule $schedule) use ($run, &$failed) {
                try {
                    $invoice = $run($schedule);
                    $this->line("schedule $schedule->id: ".($invoice ? "invoice $invoice->invoice_number" : 'nothing made'));
                } catch (Throwable $e) {
                    $failed++;
                    report($e); // one broken schedule must not stop the others
                    $this->error("schedule $schedule->id: ".$e->getMessage());
                }
            }
        );

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
