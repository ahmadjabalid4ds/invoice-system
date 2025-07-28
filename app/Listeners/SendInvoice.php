<?php

namespace App\Listeners;

use App\Events\SendWhatsappEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendInvoice
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
    public function handle(SendWhatsappEvent $event): void
    {
        $invoice = $event->invoice;
        Log::info('Invoice sent to whatsapp at ' . now()->format('Y-m-d H:i:s'));

    }
}
