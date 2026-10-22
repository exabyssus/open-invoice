<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\NumberStyles;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImportRekini extends Command
{
    protected $signature = 'rekini:import
                            {--source=rekini : Connection that holds the old "bills" database}
                            {--dry-run : Run everything and check the totals, then roll back}
                            {--paid-before= : Invoices dated before this day are imported as paid (default: 60 days ago)}
                            {--pdf-map= : CSV with bill_id,folder,file of the PDFs issued by the old system}
                            {--logo-dir= : Folder with the old /uploads files (company logos)}';

    protected $description = 'Import companies, clients, invoices and lines from the old rekini.agw.lv database';

    /** Old unit index (fuel/app Model\Lines) => Units case. */
    private const UNITS = ['SERVICE', 'HOUR', 'KILO', 'MONTH', 'HALF_YEAR', 'YEAR', 'NO_UNIT'];

    private array $notes = [];

    public function handle(): int
    {
        $src = DB::connection($this->option('source'));
        $paidBefore = Carbon::parse($this->option('paid-before') ?: now()->subDays(60))->startOfDay();
        $pdfs = $this->pdfMap();
        $dry = (bool) $this->option('dry-run');

        DB::beginTransaction();

        try {
            $rows = [];
            foreach ($src->table('bills')->where('_temp', 0)->where('owner', '<>', 0)->distinct()->orderBy('owner')->pluck('owner') as $owner) {
                $rows[] = $this->importCompany($src, (int) $owner, $paidBefore, $pdfs, $dry);
            }

            $this->table(['company', 'bills', 'new', 'lines', 'total EUR', 'debt EUR', 'pdf linked', 'checked'], $rows);
            foreach ($this->notes as $note) {
                $this->line(' - '.$note);
            }

            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->info($dry ? 'Dry run: everything checked, nothing saved.' : 'Imported.');

        return self::SUCCESS;
    }

    private function importCompany(Connection $src, int $owner, Carbon $paidBefore, array $pdfs, bool $dry): array
    {
        $sc = $src->table('companies')->where('id', $owner)->first() ?? throw new RuntimeException("Old company $owner not found");

        $company = Company::where('registration_number', $sc->reg_nr)->first();
        if (! $company) {
            $company = Company::create([
                'title' => $sc->company,
                'registration_number' => $sc->reg_nr,
                'address' => $sc->address,
                'bank_name' => $sc->bank_name ?: null,
                'bank_swift' => $sc->bank_swift ?: null,
                'bank_iban' => $sc->bank_acc ?: null,
                'number_style' => NumberStyles::NUMBER_STYLE_NONE,
            ]);
            $this->importUsers($src, $owner, $company);
            $this->notes[] = "$sc->company: created as a new company, numbers are typed by hand";
        } else {
            $missing = $src->table('users')->where('company', $owner)->pluck('email')
                ->reject(fn ($email) => ! $email || User::where('email', $email)->exists())->all();
            if ($missing) {
                $this->notes[] = "$sc->company: old users not created (company already exists): ".implode(', ', $missing);
            }
        }
        $this->importLogo($company, $sc, $dry);

        $createdBy = $company->owner_id ?? User::where('is_super_admin', true)->value('id') ?? throw new RuntimeException('No user to own the invoices');
        $clients = $this->importClients($src, $owner, $company);

        $bills = $src->table('bills')->where('_temp', 0)->where('owner', $owner)->orderBy('id')->get();
        $lines = collect();
        foreach ($bills->pluck('id')->chunk(500) as $ids) {
            $lines = $lines->concat($src->table('lines')->whereIn('mid', $ids)->orderBy('id')->get());
        }
        $lines = $lines->groupBy('mid');

        $expect = ['bills' => 0, 'lines' => 0, 'total' => 0, 'debt' => 0];
        $new = $linked = 0;
        $skippedBlank = 0;
        $seen = [];

        foreach ($bills as $bill) {
            [$rows, $blank] = $this->convertLines($lines->get($bill->id, collect()));
            $skippedBlank += $blank;

            $expect['bills']++;
            $expect['lines'] += count($rows);
            $expect['total'] += array_sum(array_column($rows, 'old_cents'));
            $expect['debt'] += $debt = (int) round($this->number($bill->debt) * 100);

            $seen[$bill->doc_nr] = ($seen[$bill->doc_nr] ?? 0) + 1;

            $existing = DB::table('invoices')->where('company_id', $company->id)->where('legacy_id', $bill->id)->first();
            if ($existing) {
                $linked += $existing->legacy_pdf ? 1 : 0;

                continue;
            }

            $ts = (int) $bill->date ?: (int) $bill->created_at;
            if (! $ts) {
                $this->notes[] = "$sc->company bill $bill->id ($bill->doc_nr) has no date, imported with today's date";
                $ts = now()->timestamp;
            }
            $date = gmdate('Y-m-d 00:00:00', $ts);
            $term = (int) $bill->term;
            $pdf = isset($pdfs[$bill->id]) && Storage::disk('private')->exists($path = "legacy-pdfs/{$pdfs[$bill->id]}") ? $path : null;
            $linked += $pdf ? 1 : 0;

            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_number' => $bill->doc_nr,
                'client_id' => $clients[$bill->client] ?? throw new RuntimeException("Bill $bill->id: client $bill->client missing"),
                'company_id' => $company->id,
                'created_by' => $createdBy,
                'date' => $date,
                'due_date' => $term > 0 ? gmdate('Y-m-d 00:00:00', $ts + 86400 * $term) : null,
                'is_advance' => (bool) $bill->avans,
                'is_paid' => Carbon::parse($date)->lt($paidBefore),
                'dept_amount' => $debt,
                'currency' => 'EUR',
                'language' => 'lv',
                'template' => 'default',
                'created_at' => $bill->created_at ? gmdate('Y-m-d H:i:s', $bill->created_at) : $date,
                'updated_at' => $bill->created_at ? gmdate('Y-m-d H:i:s', $bill->created_at) : $date,
                'deleted_at' => $bill->deleted ? now() : null,
                'legacy_id' => $bill->id,
                'legacy_pdf' => $pdf,
            ]);
            DB::table('invoice_lines')->insert(array_map(
                fn ($r) => ['invoice_id' => $invoiceId, 'created_at' => $date, 'updated_at' => $date] + array_diff_key($r, ['old_cents' => 1]),
                $rows,
            ));
            $new++;
        }

        if ($duplicates = count(array_filter($seen, fn ($n) => $n > 1))) {
            $this->notes[] = "$sc->company: $duplicates invoice numbers are used more than once (imported as they are)";
        }
        if ($skippedBlank) {
            $this->notes[] = "$sc->company: $skippedBlank blank lines (no title, no amount) skipped";
        }

        $checked = $this->verify($company, $expect);

        return [$sc->company, $expect['bills'], $new, $expect['lines'], number_format($expect['total'] / 100, 2, '.', ''), number_format($expect['debt'] / 100, 2, '.', ''), $linked, $checked];
    }

    /** Totals read back from the new tables must equal the totals computed from the old data (old PHP formula). */
    private function verify(Company $company, array $expect): string
    {
        $invoices = DB::table('invoices')->where('company_id', $company->id)->whereNotNull('legacy_id');
        $got = ['bills' => (clone $invoices)->count(), 'lines' => 0, 'total' => 0, 'debt' => (int) (clone $invoices)->sum('dept_amount')];
        foreach (DB::table('invoice_lines')->whereIn('invoice_id', (clone $invoices)->select('id'))->cursor() as $line) {
            $got['lines']++;
            $got['total'] += (int) round($line->quantity * $line->price);
        }

        if ($got !== $expect) {
            throw new RuntimeException("{$company->title}: totals differ, expected ".json_encode($expect).' got '.json_encode($got));
        }

        return 'ok';
    }

    /** @return array{0: array<int, array>, 1: int} converted lines and the number of skipped blank lines */
    private function convertLines($lines): array
    {
        $rows = [];
        $blank = 0;
        foreach ($lines as $l) {
            $price = $this->number($l->price);
            $qty = $this->number($l->quantity);
            $title = trim($l->title);
            if ($title === '' && $price * $qty == 0) {
                $blank++;

                continue;
            }
            $rows[] = [
                'title' => $title,
                'quantity' => $qty,
                'price' => (int) round($price * 100),
                'vat' => null,
                'unit' => ctype_digit((string) $l->unit) ? (self::UNITS[(int) $l->unit] ?? 'NO_UNIT') : 'NO_UNIT',
                'old_cents' => (int) round($price * $qty * 100),
            ];
        }

        return [$rows, $blank];
    }

    /** Reads numbers like the old PHP did: leading numeric part only ("300,00" is 300, "2.67\t" is 2.67). */
    private function number(mixed $value): float
    {
        return preg_match('/^\s*(-?\d+(?:\.\d+)?)/', (string) $value, $m) ? (float) $m[1] : 0.0;
    }

    private function importClients(Connection $src, int $owner, Company $company): array
    {
        $map = [];
        foreach ($src->table('clients')->where('owner', $owner)->orderBy('id')->get() as $c) {
            $map[$c->id] = DB::table('clients')->where('company_id', $company->id)->where('legacy_id', $c->id)->value('id')
                ?? DB::table('clients')->insertGetId([
                    'company_id' => $company->id,
                    'title' => $c->name,
                    'registration_number' => $c->reg_nr ?: null,
                    'address' => $c->address ?: null,
                    'agreement_number' => $c->contract_nr ?: null,
                    'bank_name' => $c->bank_name ?: null,
                    'bank_swift' => $c->bank_swift ?: null,
                    'bank_iban' => $c->bank_acc ?: null,
                    'created_at' => $c->created_at ? gmdate('Y-m-d H:i:s', $c->created_at) : now(),
                    'updated_at' => now(),
                    'deleted_at' => $c->deleted ? now() : null,
                    'legacy_id' => $c->id,
                ]);
        }

        return $map;
    }

    /** Only for companies created by this import; for existing ones the users stay as they are. */
    private function importUsers(Connection $src, int $owner, Company $company): void
    {
        foreach ($src->table('users')->where('company', $owner)->get() as $u) {
            if (! $u->email) {
                continue;
            }
            // random password: the person gets in through "forgot password"
            $user = User::firstOrCreate(['email' => $u->email], ['name' => $u->username, 'password' => Str::random(40)]);
            $company->users()->syncWithoutDetaching([$user->id]);
            $company->owner_id ??= $user->id;
        }
        $company->save();
    }

    private function importLogo(Company $company, object $sc, bool $dry): void
    {
        $dir = $this->option('logo-dir');
        if ($company->logo || ! $sc->logo || ! $dir || ! is_file($file = rtrim($dir, '/').'/'.basename($sc->logo))) {
            return;
        }
        if (! $dry) {
            Storage::disk('private')->put($path = 'logos/'.basename($file), file_get_contents($file));
            $company->update(['logo' => $path]);
        }
        $this->notes[] = "$sc->company: logo ".basename($file).' '.($dry ? 'would be copied' : 'copied');
    }

    /** @return array<int, string> bill id => "folder/file" */
    private function pdfMap(): array
    {
        if (! $file = $this->option('pdf-map')) {
            return [];
        }
        $map = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            [$id, $folder, $name] = str_getcsv($line);
            $map[(int) $id] = "$folder/$name";
        }

        return $map;
    }
}
