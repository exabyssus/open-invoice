<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\GenerateInvoicePdf;
use Filament\Pages\BasePage;
use Filament\Resources\Pages\PageRegistration;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewInvoicePdf extends BasePage
{
    protected static string $resource = InvoiceResource::class;

    public static function route(string $path): PageRegistration
    {
        // a controller method, not a closure: `route:cache` (run at every container start) picks the wrong one of two closures on a line
        return new PageRegistration(
            page: static::class,
            route: fn (): Route => RouteFacade::get('/{record}/pdf/{method}', [static::class, 'serve']),
        );
    }

    public function serve($tenantId, $invoiceId, $method): StreamedResponse
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::query()->findOrFail($invoiceId);
        $filename = Str::slug($invoice->invoice_number . '-' . $invoice->client->title);

        return response()->stream(function () use ($invoice) {
            // imported invoices keep the PDF that was issued by the old system until they are edited
            if ($invoice->legacy_pdf && Storage::disk('private')->exists($invoice->legacy_pdf)) {
                echo Storage::disk('private')->get($invoice->legacy_pdf);

                return;
            }
            /** @var GenerateInvoicePdf $generator */
            $generator = app(GenerateInvoicePdf::class);
            echo $generator($invoice)->content();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $method . '; filename="'. $filename . '.pdf'.'"'
        ]);
    }
}
