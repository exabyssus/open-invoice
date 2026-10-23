<?php

use App\Filament\Pages\ListInvoices;
use App\Filament\Resources\InvoiceScheduleResource;
use App\Filament\Resources\InvoiceScheduleResource\Pages\CreateInvoiceSchedule;
use App\Filament\Resources\InvoiceScheduleResource\Pages\ListInvoiceSchedules;
use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceSchedule;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->company->update(['number_style' => 'NUMBER_STYLE_ALL_TIME', 'number_prefix' => 'R-', 'number_suffix' => '']);
    $this->client = Client::factory()->create(['company_id' => $this->company->id, 'email' => 'klients@example.test']);
    Carbon::setTestNow('2026-11-10 12:00:00');
});

afterEach(fn () => Carbon::setTestNow());

function makeSchedule($test, array $attributes = [], ?Company $company = null): InvoiceSchedule
{
    $schedule = new InvoiceSchedule($attributes + [
        'client_id' => $test->client->id,
        'frequency' => 'MONTHLY',
        'day' => 15,
        'due_days' => 14,
        'auto_send' => false,
        'lines' => [['title' => 'Hosting', 'quantity' => 2, 'price' => 2500, 'vat' => null, 'unit' => 'MONTH']],
    ]);
    $schedule->forceFill(['company_id' => ($company ?? $test->company)->id, 'created_by' => $test->user->id])->save();

    return $schedule;
}

/** The scheduler runs without a logged-in user or a tenant. */
function runDaily($test): void
{
    auth()->logout();
    Filament::setTenant(null, true);
    $test->artisan('invoices:run-schedules')->assertSuccessful();
}

it('computes the first run when a schedule is saved', function () {
    $s = makeSchedule($this);

    expect($s->next_run_on->toDateString())->toBe('2026-11-15');

    $s->update(['day' => 5]);
    expect($s->fresh()->next_run_on->toDateString())->toBe('2026-12-05');
});

it('makes a regular invoice on the day and moves to the next date', function () {
    $s = makeSchedule($this);
    Carbon::setTestNow('2026-11-15 06:00:00');

    runDaily($this);

    $invoice = Invoice::withoutGlobalScopes()->sole();
    expect($invoice->invoice_number)->toBe('R-1') // the company's number style
        ->and($invoice->company_id)->toBe($this->company->id)
        ->and($invoice->client_id)->toBe($this->client->id)
        ->and($invoice->created_by)->toBe($this->user->id)
        ->and($invoice->date->toDateString())->toBe('2026-11-15')
        ->and($invoice->due_date->toDateString())->toBe('2026-11-29')
        ->and($invoice->is_paid)->toBeFalse()
        ->and($invoice->template)->toBe('default')
        ->and($invoice->lines()->count())->toBe(1)
        ->and($invoice->lines()->first()->price)->toBe(2500)
        ->and($s->fresh()->next_run_on->toDateString())->toBe('2026-12-15')
        ->and($s->fresh()->last_run_at)->not->toBeNull();
});

it('skips schedules that are not due or not active, and does not repeat on the same day', function () {
    makeSchedule($this, ['day' => 16]);                        // tomorrow
    makeSchedule($this, ['is_active' => false, 'day' => 15]);  // due but paused
    makeSchedule($this);
    Carbon::setTestNow('2026-11-15 06:00:00');

    runDaily($this);
    runDaily($this); // a second run the same day

    expect(Invoice::withoutGlobalScopes()->count())->toBe(1);
});

it('makes one invoice for a missed period, not one per missed day', function () {
    $s = makeSchedule($this);
    Carbon::setTestNow('2026-11-19 06:00:00'); // the server missed the 15th

    runDaily($this);

    expect(Invoice::withoutGlobalScopes()->count())->toBe(1)
        ->and(Invoice::withoutGlobalScopes()->first()->date->toDateString())->toBe('2026-11-19')
        ->and($s->fresh()->next_run_on->toDateString())->toBe('2026-12-15');
});

it('mails the invoice as a PDF when auto send is on', function () {
    makeSchedule($this, ['auto_send' => true]);
    Carbon::setTestNow('2026-11-15 06:00:00');

    runDaily($this);

    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    $mail = $messages->first()->getOriginalMessage();
    expect($mail->getTo()[0]->getAddress())->toBe('klients@example.test')
        ->and($mail->getSubject())->toContain('R-1')
        ->and($mail->getReplyTo()[0]->getAddress())->toBe($this->user->email)
        ->and($mail->getAttachments())->toHaveCount(1)
        ->and($mail->getAttachments()[0]->getBody())->toStartWith('%PDF')
        ->and(Invoice::withoutGlobalScopes()->sole()->sent_at)->not->toBeNull();
});

it('does not mail without auto send or without a client email', function () {
    makeSchedule($this);
    $this->client->update(['email' => null]);
    makeSchedule($this, ['auto_send' => true]);
    Carbon::setTestNow('2026-11-15 06:00:00');

    runDaily($this);

    expect(app('mailer')->getSymfonyTransport()->messages())->toHaveCount(0)
        ->and(Invoice::withoutGlobalScopes()->count())->toBe(2)
        ->and(Invoice::withoutGlobalScopes()->whereNotNull('sent_at')->count())->toBe(0);
});

it('keeps the invoice when sending fails', function () {
    makeSchedule($this, ['auto_send' => true]);
    Carbon::setTestNow('2026-11-15 06:00:00');
    Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));

    runDaily($this);

    $invoice = Invoice::withoutGlobalScopes()->sole();
    expect($invoice->sent_at)->toBeNull();
});

it('pauses a schedule whose client is gone and carries on with the others', function () {
    $gone = makeSchedule($this, ['client_id' => Client::factory()->create(['company_id' => $this->company->id])->id]);
    Client::withoutGlobalScopes()->find($gone->client_id)->forceDelete();
    makeSchedule($this);
    Carbon::setTestNow('2026-11-15 06:00:00');

    runDaily($this);

    expect($gone->fresh()->is_active)->toBeFalse()
        ->and(Invoice::withoutGlobalScopes()->count())->toBe(1);
});

it('can list, create and run a schedule in the panel', function () {
    $this->get(InvoiceScheduleResource::getUrl('index', ['tenant' => $this->company]))->assertOk();
    $this->get(InvoiceScheduleResource::getUrl('create', ['tenant' => $this->company]))->assertOk();

    Livewire::test(CreateInvoiceSchedule::class, ['tenant' => $this->company])
        ->fillForm([
            'client_id' => $this->client->id,
            'frequency' => 'YEARLY',
            'month' => 3,
            'day' => 1,
            'due_days' => 30,
            'auto_send' => true,
            'lines' => [['title' => 'Licence', 'price' => '120,00', 'unit' => 'YEAR', 'quantity' => '1']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $s = InvoiceSchedule::sole();
    expect($s->company_id)->toBe($this->company->id)
        ->and($s->created_by)->toBe($this->user->id)
        ->and($s->lines[0]['price'])->toBe(12000)
        ->and($s->next_run_on->toDateString())->toBe('2027-03-01');

    Livewire::test(ListInvoiceSchedules::class, ['tenant' => $this->company])
        ->assertCanSeeTableRecords([$s])
        ->callTableAction('runNow', $s);

    expect(Invoice::count())->toBe(1)
        ->and($s->fresh()->next_run_on->toDateString())->toBe('2027-03-01'); // a manual run leaves the date alone

    Livewire::test(ListInvoices::class, ['tenant' => $this->company])->assertSuccessful();
});

it('refuses auto send for a client without an email', function () {
    $this->client->update(['email' => null]);

    Livewire::test(CreateInvoiceSchedule::class, ['tenant' => $this->company])
        ->fillForm([
            'client_id' => $this->client->id,
            'frequency' => 'MONTHLY',
            'day' => 1,
            'auto_send' => true,
            'lines' => [['title' => 'X', 'price' => '1,00', 'unit' => 'MONTH', 'quantity' => '1']],
        ])
        ->call('create')
        ->assertHasFormErrors(['auto_send']);
});

it('does not show the schedules of another company', function () {
    $other = Company::factory()->create();
    $theirs = makeSchedule($this, [], $other);

    $this->get(InvoiceScheduleResource::getUrl('edit', ['tenant' => $this->company, 'record' => $theirs]))->assertNotFound();
});
