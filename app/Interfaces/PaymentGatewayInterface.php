<?php

namespace App\Interfaces;

use App\Models\Invoice;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function sendPayment(Request $request): array;
    public function callBack(Request $request): bool;
    public function executePayment(array $data): array;
}
