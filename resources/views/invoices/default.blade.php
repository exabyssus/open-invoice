@php use App\Support\Money; @endphp
@php
    /** @var $invoice \App\Models\Invoice */
    /** @var $company \App\Models\Company */
    /** @var $client \App\Models\Company */
$client->contract_number = null;
@endphp
<html lang="lv">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
        }

        th {
            text-align: left;
        }

        .pad td, .pad th {
            padding: 5px;
        }

        .pad-lg td, .pad-lg th {
            padding: 10px 15px;
        }

        .pad-v td, .pad-v th {
            padding: 7px 0;
        }

        .company-properties {
            margin-top: 20px;
        }

        .company-properties td, .company-properties th {
            line-height: 10px;
            padding: 5px 8px 5px 0;
        }

        .border-b td, .border-b th {
            border-bottom: 1px solid #666;
        }

        h2 {
            font-size: 16px;
            font-weight: normal;
        }

        h1 {
            font-size: 18px;
            color: #444;
            font-weight: normal;
        }

        table {
            margin: 0;
            border-collapse: collapse;
            vertical-align: top;
            width: 100%;
            border: none;
            font-size: 12px;
        }

        table.dk-table {
            font-size: 8px;
            color: #666;
        }

        table.dk-table td {
            border: 1px solid #ccc;
            text-align: center;
        }
    </style>
</head>
<body>
<table width="100%">
    <tr>
        <td width="50%">
            @if($logo)
                <img src="data:image/png;base64, {{ base64_encode($logo) }}" width="222px">
            @else
                <h1 style="font-weight: bold">{{ $company->title }}</h1>
            @endif
        </td>
        <td width="50%" style="text-align: right;">
            <h2>
                <span style="color: #666;">@if($invoice->advance)
                        AVANSA
                    @endif RĒĶINA NR.: </span>{{ $invoice->invoice_number }}
            </h2>
            <div style="font-size: 15px;">
                <span style="color: #666;">DATUMS: </span>{{ $invoice->date->format('d.m.Y') }}
            </div>
        </td>
    </tr>
</table>

<div style="margin-top: 50px">
    <table>
        <tr>
            <td width="49%" style="vertical-align: top;">
                <h1>PAKALPOJUMU SNIEDZĒJS</h1>
                <table class="company-properties">
                    <tr>
                        <th>Nosaukums</th>
                        <th>{{ $company->title }}</th>
                    </tr>
                    @if($company->registration_number)
                        <tr>
                            <th>Rēģistrācijas nr.</th>
                            <td>{{ $company->registration_number }}</td>
                        </tr>
                    @endif
                    @if($company->vat_number)
                        <tr>
                            <th>PVN nr.</th>
                            <td>{{ $company->vat_number }}</td>
                        </tr>
                    @endif
                    @if($company->address)
                        <tr>
                            <th>Jur. adrese</th>
                            <td>{{ $company->address }}</td>
                        </tr>
                    @endif
                    @if($company->bank_name)
                        <tr>
                            <th>Bankas nosaukums</th>
                            <td>{{ $company->bank_name }}</td>
                        </tr>
                    @endif
                    @if($company->bank_swift)
                        <tr>
                            <th>SWIFT kods</th>
                            <td>{{ $company->bank_swift }}</td>
                        </tr>
                    @endif
                    @if($company->bank_iban)
                        <tr>
                            <th>Norēķinu konts</th>
                            <td>{{ $company->bank_iban }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            <td width="2%"></td>
            <td width="49%" class="company-properties" style="vertical-align: top;">
                <h1>MAKSĀTĀJS</h1>

                <table style="margin-top: 20px;">
                    <tr>
                        <th>Nosaukums</th>
                        <th>{{ $client->title }}</th>
                    </tr>
                    @if($client->registration_number)
                        <tr>
                            <th>Rēģistrācijas nr.</th>
                            <td>{{ $client->registration_number }}</td>
                        </tr>
                    @endif
                    @if($company->vat_number)
                        <tr>
                            <th>PVN nr.</th>
                            <td>{{ $company->vat_number }}</td>
                        </tr>
                    @endif
                    @if($client->address)
                        <tr>
                            <th>Jur. adrese</th>
                            <td>{{ $client->address }}</td>
                        </tr>
                    @endif
                    @if($client->bank_name)
                        <tr>
                            <th>Bankas nosaukums</th>
                            <td>{{ $client->bank_name }}</td>
                        </tr>
                    @endif
                    @if($client->bank_swift)
                        <tr>
                            <th>SWIFT kods</th>
                            <td>{{ $client->bank_swift }}</td>
                        </tr>
                    @endif
                    @if($client->bank_iban)
                        <tr>
                            <th>Norēķinu konts</th>
                            <td>{{ $client->bank_iban }}</td>
                        </tr>
                    @endif
                </table>

            </td>
        </tr>
    </table>
</div>

<table width="100%" style="margin-top: 50px;">
    <tr>
        <td width="34%">
            @if($invoice->due_date)
                <div>Apmaksas termiņš: {{ $invoice->due_date->format('d.m.Y') }}</div>
            @endif
        </td>

        <td width="33%" style="text-align: center">
            @if($client->contract_number)
                Līguma nr.: {{ $client->contract_number }}
            @endif
        </td>

        <td width="33%" style="text-align: right">
            <div>Apmaksas veids: Pārskaitījums</div>
        </td>
    </tr>
</table>


<table border="0" width="100%" style="margin-top: 20px;" class="pad-lg">

    <tr class="border-b">
        <th width="5%" style="text-align: center">Nr.</th>
        <th width="40%">Nosaukums</th>
        <th style="text-align: center">Mērv.</th>
        <th style="text-align: center">Daudzums</th>
        <th style="text-align: center">Cena</th>
        <th style="text-align: center">Summa</th>
    </tr>

    @php
        /** @var $line \App\Models\InvoiceLine */
    @endphp
    @foreach($lines as $index => $line)
        <tr class="border-b">
            <td style="text-align: center">{{ $index + 1 }}</td>
            <td>{{ $line->title }}</td>
            <td style="text-align: center">Stundas</td>
            <td style="text-align: center">{{ $line->quantity }}</td>
            <td style="text-align: center">{{ Money::defaultFormat($line->price) }}</td>
            <td style="text-align: center">{{ Money::defaultFormat($line->total) }}</td>
        </tr>
    @endforeach

    @if($invoice->getTotalVAT()->isPositive() || $invoice->getTotalDept()->isPositive())
        <tr>
            <td colspan="5" style="text-align: right">Kopā:</td>
            <td style="text-align: center;">{{ Money::defaultFormat($invoice->getTotal()) }}</td>
        </tr>
    @endif

    @if($invoice->getTotalDept()->isPositive())
        <tr>
            <td colspan="5" style="text-align: right">Kavēts maksājums:</td>
            <td style="text-align: center;">{{ Money::defaultFormat($invoice->getTotalDept()) }}</td>
        </tr>
    @endif

    @if($invoice->getTotalVAT()->isPositive())
        <tr>
            <td colspan="5" style="text-align: right">PVN 21%:</td>
            <td style="text-align: center;">{{ Money::defaultFormat($invoice->getTotalVAT()) }}</td>
        </tr>
    @endif

    <tr>
        <th colspan="5" style="text-align: right">Summa apmaksai:</th>
        <th style="text-align: center;">{{ Money::defaultFormat($invoice->getTotalWithVatAndTax()) }}</th>
    </tr>

</table>

<div style="font-size: 12px; margin-top: 20px; margin-bottom: 50px">
    Summa apmaksai vārdiem: {!! $totalAsText !!}
</div>

<div style="float: left; width:70%;">
    <div style="font-size: 12px;">
        Rēķinu izrakstīja: {{ $invoice->createdByUser->name }}
    </div>
    <div style="font-size: 12px; margin-top: 20px">
        Datums: {{ $invoice->date->format('d.m.Y') }}
    </div>
</div>

@if($company->show_accounts)
    <div style="float: left; width: 30%;">
        <table width="100%" class="pad dk-table">
            <tr>
                <td>D 2310</td>
                <td>K 6110</td>
                <td>{{ Money::defaultFormat($invoice->getTotal()) }}</td>
            </tr>
            <tr>
                <td>D 2310</td>
                <td>K 5721</td>
                <td>{{ Money::defaultFormat($invoice->getTotalVAT()) }}</td>
            </tr>
            <tr>
                <td>D 2620</td>
                <td>K 2310</td>
                <td>{{ Money::defaultFormat($invoice->getTotal()->add($invoice->getTotalVAT())) }}</td>
            </tr>
        </table>
    </div>
@endif
</body>
</html>
