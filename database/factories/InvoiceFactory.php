<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Money\Currency;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'XX-1',
            'client_id' => 1,
            'currency' => 'EUR',
            'date' => Carbon::now()
        ];
    }
}
