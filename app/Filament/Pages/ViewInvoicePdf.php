<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\GenerateInvoicePdf;
use Filament\Pages\BasePage;
use Filament\Resources\Pages\PageRegistration;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

class ViewInvoicePdf extends BasePage
{
    protected static string $resource = InvoiceResource::class;

    public static function route(string $path): PageRegistration
    {
        return new PageRegistration(
            page: static::class,
            route: fn (): Route => RouteFacade::get('/{record}/pdf/{method}', function ($tenantId, $invoiceId, $method) {
                /** @var Invoice $invoice */
                $invoice = Invoice::query()->findOrFail($invoiceId);
                $filename = Str::slug($invoice->invoice_number . '-' . $invoice->client->title);

                return response()->stream(function () use ($invoice) {
                    /** @var GenerateInvoicePdf $generator */
                    $generator = app(GenerateInvoicePdf::class);
                    echo $generator($invoice)->content();
                }, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => $method . '; filename="'. $filename . '.pdf'.'"'
                ]);
            })
        );
    }
}
