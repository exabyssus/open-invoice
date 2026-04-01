<?php

use App\Filament\Pages\CreateInvoice;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use Livewire\Livewire;

it('can render product list page', function () {
    $this->get(ProductResource::getUrl('index', [
        'tenant' => $this->company,
    ]))->assertOk();
});

it('can create a product', function () {
    Livewire::test(CreateProduct::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'name' => 'Web Development',
            'product_number' => 'WEB-001',
            'price' => '50,00',
            'default_quantity' => '1',
            'unit' => 'HOUR',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', [
        'name' => 'Web Development',
        'product_number' => 'WEB-001',
        'price' => 5000,
        'company_id' => $this->company->id,
    ]);
});

it('can edit a product', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    Livewire::test(EditProduct::class, [
        'tenant' => $this->company,
        'record' => $product->id,
    ])
        ->fillForm([
            'name' => 'Updated Product',
            'price' => '99,99',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $product->refresh();
    expect($product->name)->toBe('Updated Product');
    expect($product->price)->toBe(9999);
});

it('can list products', function () {
    $products = Product::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    Livewire::test(ListProducts::class, [
        'tenant' => $this->company,
    ])
        ->assertCanSeeTableRecords($products);
});

it('product fills invoice line fields via suffix action', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Consulting',
        'product_number' => 'CON-001',
        'price' => 15000,
        'default_quantity' => 2.5,
        'unit' => 'HOUR',
    ]);

    $client = Client::factory()->create(['company_id' => $this->company->id]);

    $livewire = Livewire::test(CreateInvoice::class, [
        'tenant' => $this->company,
    ]);

    // Fill the invoice line using the product action
    $livewire
        ->fillForm([
            'invoice_number' => 'PROD-001',
            'client_id' => $client->id,
            'date' => '2026-04-01',
            'currency' => 'EUR',
            'dept_amount' => '0,00',
            'lines' => [
                [
                    'title' => 'Consulting (CON-001)',
                    'price' => '150,00',
                    'unit' => 'HOUR',
                    'quantity' => '2,5',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::where('invoice_number', 'PROD-001')->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->lines)->toHaveCount(1);

    $line = $invoice->lines->first();
    expect($line->title)->toBe('Consulting (CON-001)');
    expect($line->getRawOriginal('price'))->toBe(15000);
    expect((float) $line->quantity)->toBe(2.5);
    expect($line->unit)->toBe('HOUR');
});
