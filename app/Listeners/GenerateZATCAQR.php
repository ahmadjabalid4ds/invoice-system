<?php

namespace App\Listeners;

use App\Events\InvoicePaidEvent;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class GenerateZATCAQR
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(InvoicePaidEvent $event): void
    {
        $invoice_id = $event->invoiceId;
        $invoice = Invoice::query()->with('tenant')->find($invoice_id);
        $tenant = $invoice->tenant;

        $generatedString = GenerateQrCode::fromArray([
            new Seller($tenant->name),
            new TaxNumber($tenant->cr_number),
            new InvoiceDate(Carbon::make($invoice->created_at)->toIso8601ZuluString()),
            new InvoiceTotalAmount($invoice->total),
            new InvoiceTaxAmount($invoice->vat)
        ])->toBase64();

        Log::info("Generated QR:" . $generatedString);

        $invoice->update(['zatca_qr' => $generatedString]);
    }
}
