<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdf
{
    public function __invoke(Invoice $invoice): Response
    {
        return $this->generatePdf($invoice);
    }

    public function generatePdf(Invoice $invoice): Response
    {
        Pdf::setOption([
            'dpi' => 150,
            'defaultPaperSize' => 'A4',
            'defaultFont' => 'DejaVu Sans'
        ]);

        $pdf = Pdf::loadView('invoices.' . $invoice->template, [
            'invoice' => $invoice,
            'logo' => $this->getLogo($invoice->company),
            'client' => $invoice->client,
            'company' => $invoice->company,
            'lines' => $invoice->lines,
            'totalAsText' => (new GenerateTextualPrice())($invoice->getTotalWithVatAndTax()->getAmount() / 100),
        ]);

        return $pdf->stream();
    }

    public function getLogo(Company $company): ?string
    {
        if (! $company->logo || ! Storage::disk('private')->exists($company->logo)) {
            return null;
        }

        return Storage::disk('private')->get($company->logo);
    }
}
