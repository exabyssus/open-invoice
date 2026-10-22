<?php

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['database.connections.rekini' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    DB::purge('rekini');
    $old = Schema::connection('rekini');

    $old->create('companies', fn ($t) => [$t->integer('id'), $t->string('company'), $t->string('reg_nr'), $t->string('address'), $t->string('bank_name'), $t->string('bank_swift'), $t->string('bank_acc'), $t->string('logo')]);
    $old->create('users', fn ($t) => [$t->integer('id'), $t->string('username'), $t->string('email'), $t->integer('company')]);
    $old->create('clients', fn ($t) => [$t->integer('id'), $t->integer('created_at'), $t->integer('deleted'), $t->string('name'), $t->string('reg_nr'), $t->string('address'), $t->integer('owner'), $t->string('bank_acc'), $t->string('bank_name'), $t->string('bank_swift'), $t->string('contract_nr')]);
    $old->create('bills', fn ($t) => [$t->integer('id'), $t->integer('_temp'), $t->integer('created_at'), $t->integer('deleted'), $t->string('doc_nr'), $t->string('client'), $t->integer('date'), $t->integer('avans'), $t->string('term'), $t->integer('owner'), $t->string('debt')]);
    $old->create('lines', fn ($t) => [$t->integer('id'), $t->integer('mid'), $t->string('title'), $t->string('quantity'), $t->string('unit'), $t->string('price')]);

    $src = DB::connection('rekini');
    // owner 1 exists in the app (same registration number), owner 3 does not
    $src->table('companies')->insert([
        ['id' => 1, 'company' => 'Old AGW', 'reg_nr' => $this->company->registration_number, 'address' => '', 'bank_name' => '', 'bank_swift' => '', 'bank_acc' => '', 'logo' => ''],
        ['id' => 3, 'company' => 'SIA LadyBug', 'reg_nr' => '40103397342', 'address' => 'Riga', 'bank_name' => 'SEB', 'bank_swift' => 'UNLALV2X', 'bank_acc' => 'LV50', 'logo' => ''],
    ]);
    $src->table('users')->insert([['id' => 2, 'username' => 'Diana', 'email' => 'diana@ladybug.test', 'company' => 3]]);
    $client = ['created_at' => 0, 'reg_nr' => '', 'address' => '', 'bank_acc' => '', 'bank_name' => '', 'bank_swift' => '', 'contract_nr' => 'K-7'];
    $src->table('clients')->insert([
        ['id' => 10, 'deleted' => 0, 'name' => 'Client A', 'owner' => 1] + $client,
        ['id' => 11, 'deleted' => 1, 'name' => 'Gone Client', 'owner' => 1] + $client,
        ['id' => 30, 'deleted' => 0, 'name' => 'Lady client', 'owner' => 3] + $client,
    ]);
    $bill = ['_temp' => 0, 'created_at' => 1700000000, 'deleted' => 0, 'avans' => 0, 'term' => '0', 'debt' => '0.00'];
    $src->table('bills')->insert([
        ['id' => 100, 'doc_nr' => 'AGW2020-001', 'client' => '10', 'date' => 1577836800, 'owner' => 1, 'term' => '14', 'debt' => '5.50'] + $bill,
        ['id' => 101, 'doc_nr' => 'AGW2020-002', 'client' => '11', 'date' => 1577923200, 'owner' => 1, 'avans' => 1] + $bill,
        ['id' => 300, 'doc_nr' => '010120-S1', 'client' => '30', 'date' => 1577836800, 'owner' => 3] + $bill,
        ['id' => 900, 'doc_nr' => '', 'client' => '', 'date' => 0, 'owner' => 0, '_temp' => 1429174174, 'created_at' => 0, 'deleted' => 0, 'avans' => 0, 'term' => '', 'debt' => '0.00'],
    ]);
    $src->table('lines')->insert([
        ['id' => 1, 'mid' => 100, 'title' => 'Dirty', 'quantity' => "2.67\t", 'unit' => '1', 'price' => '30'],
        ['id' => 2, 'mid' => 100, 'title' => 'Comma', 'quantity' => '1', 'unit' => '4', 'price' => '300,00 '],
        ['id' => 3, 'mid' => 100, 'title' => '', 'quantity' => '', 'unit' => '', 'price' => ''],
        ['id' => 4, 'mid' => 101, 'title' => 'Advance', 'quantity' => '1', 'unit' => '0', 'price' => '-200'],
        ['id' => 5, 'mid' => 300, 'title' => 'Monthly', 'quantity' => '12.5', 'unit' => '3', 'price' => '1520.20'],
        ['id' => 6, 'mid' => 999, 'title' => 'Orphan', 'quantity' => '1', 'unit' => '0', 'price' => '1'],
    ]);
});

it('imports companies, clients, invoices and lines with the old totals', function () {
    $this->artisan('rekini:import', ['--paid-before' => '2020-01-02'])->assertSuccessful();

    $invoices = DB::table('invoices')->whereNotNull('legacy_id');
    expect($invoices->count())->toBe(3); // the empty _temp draft is skipped

    $one = DB::table('invoices')->where('legacy_id', 100)->first();
    expect($one->company_id)->toBe($this->company->id)
        ->and($one->dept_amount)->toBe(550)
        ->and($one->date)->toBe('2020-01-01 00:00:00')
        ->and($one->due_date)->toBe('2020-01-15 00:00:00')
        ->and((bool) $one->is_paid)->toBeTrue();

    $lines = DB::table('invoice_lines')->where('invoice_id', $one->id)->orderBy('id')->get();
    expect($lines)->toHaveCount(2) // blank line skipped
        ->and($lines[0]->quantity)->toBe(2.67)->and($lines[0]->price)->toBe(3000)->and($lines[0]->unit)->toBe('HOUR')
        ->and($lines[1]->price)->toBe(30000)->and($lines[1]->unit)->toBe('HALF_YEAR');

    expect((bool) DB::table('invoices')->where('legacy_id', 101)->value('is_advance'))->toBeTrue();
    expect(DB::table('clients')->where('legacy_id', 11)->value('deleted_at'))->not->toBeNull();
    expect(DB::table('clients')->where('legacy_id', 10)->value('agreement_number'))->toBe('K-7');

    $lady = Company::where('registration_number', '40103397342')->first();
    expect($lady->users()->pluck('email')->all())->toBe(['diana@ladybug.test'])
        ->and(DB::table('invoices')->where('legacy_id', 300)->value('created_by'))->toBe($lady->owner_id);
});

it('does nothing on a dry run and is repeatable', function () {
    $this->artisan('rekini:import', ['--dry-run' => true])->assertSuccessful();
    expect(DB::table('invoices')->count())->toBe(0)
        ->and(Company::where('registration_number', '40103397342')->exists())->toBeFalse();

    $this->artisan('rekini:import')->assertSuccessful();
    $this->artisan('rekini:import')->assertSuccessful();
    expect(DB::table('invoices')->count())->toBe(3)->and(DB::table('clients')->whereNotNull('legacy_id')->count())->toBe(3);
});

it('fails when an imported invoice changed in the old data', function () {
    $this->artisan('rekini:import')->assertSuccessful();
    DB::connection('rekini')->table('lines')->where('id', 5)->update(['price' => '1']);

    expect(fn () => $this->artisan('rekini:import')->run())->toThrow(RuntimeException::class, 'totals differ');
});
