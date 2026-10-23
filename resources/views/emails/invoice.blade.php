{{ __('mail.invoice.greeting') }}

{{ __('mail.invoice.body', ['number' => $invoice->invoice_number, 'company' => $company->title, 'date' => $invoice->date->format('d.m.Y')]) }}
{{ __('mail.invoice.total') }}: {{ $total }}
@if($invoice->due_date)
{{ __('mail.invoice.due') }}: {{ $invoice->due_date->format('d.m.Y') }}
@endif

{{ __('mail.invoice.regards') }}
{{ $company->title }}
