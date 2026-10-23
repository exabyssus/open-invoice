<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceSchedule;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class RunInvoiceSchedule
{
    /**
     * Makes today's invoice of a schedule, like a regular one (number from the company's style, lines from the template),
     * and mails it to the client when auto_send is on. $advance moves next_run_on past today (the daily run), a manual run leaves it.
     * Returns null when nothing was made (invoice of today already exists, client gone).
     */
    public function __invoke(InvoiceSchedule $schedule, bool $advance = true): ?Invoice
    {
        $before = Filament::getTenant();
        Filament::setTenant($schedule->company, isQuiet: true); // the models' tenant scopes (clients, invoices) need it

        try {
            $client = Client::find($schedule->client_id);
            if (! $client) {
                $schedule->update(['is_active' => false]);
                report(new RuntimeException("Invoice schedule $schedule->id paused: client $schedule->client_id is gone"));

                return null;
            }

            $today = Carbon::today();
            $made = Invoice::withTrashed()->where('invoice_schedule_id', $schedule->id)->whereDate('date', $today)->exists();

            $invoice = $made ? null : DB::transaction(function () use ($schedule, $client, $today, $advance) {
                $invoice = new Invoice([
                    // shortcut: a company with typed numbers has no number to generate, the date stands in; upgrade by giving schedules a number template
                    'invoice_number' => (new GenerateInvoiceNumber)($schedule->company, $client) ?: $today->format('Y.m.d'),
                    'client_id' => $client->id,
                    'date' => $today,
                    'due_date' => $schedule->due_days !== null ? $today->copy()->addDays($schedule->due_days) : null,
                    'is_advance' => false,
                    'is_paid' => false,
                    'dept_amount' => 0,
                    'comment' => $schedule->comment,
                    'currency' => 'EUR',
                    'language' => 'lv',
                ]);
                $invoice->forceFill(['company_id' => $schedule->company_id, 'created_by' => $schedule->created_by, 'invoice_schedule_id' => $schedule->id])->save();
                $invoice->lines()->createMany($schedule->lines);

                $schedule->forceFill(['last_run_at' => now()])->save();

                return $invoice;
            });

            if ($advance) {
                $schedule->forceFill(['next_run_on' => $schedule->occurrenceOnOrAfter($today->copy()->addDay())])->save();
            }

            if ($invoice && $schedule->auto_send) {
                $this->send($invoice, $client);
            }

            return $invoice;
        } finally {
            Filament::setTenant($before, isQuiet: true);
        }
    }

    private function send(Invoice $invoice, Client $client): void
    {
        if (! $client->email) {
            Log::warning("Invoice $invoice->invoice_number not sent: client $client->title has no email");

            return;
        }

        try {
            Mail::to($client->email)->send(new InvoiceMail($invoice));
            $invoice->forceFill(['sent_at' => now()])->saveQuietly();
        } catch (Throwable $e) {
            report($e); // the invoice stays, sent_at stays empty (shown in the invoice list)
        }
    }
}
