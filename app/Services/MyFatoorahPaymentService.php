<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MyFatoorahPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    protected $api_key;

    public function __construct()
    {
        $this->base_url = config('services.my_fatoorah.base_url');
        $this->api_key = config('services.my_fatoorah.api_key');
        $this->header = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key,
        ];
    }

    public function sendPayment(Request $request): array
    {
        Log::info('MyFatoorah SendPayment Request', $request->all());
        try {
            $data = [
                'InvoiceValue' => $request->input('InvoiceValue'),
                'CustomerName' => $request->input('CustomerName'),
                'CustomerEmail' => $request->input('CustomerEmail'),
                'DisplayCurrencyIso' => $request->input('DisplayCurrencyIso', 'KWD'),
                'Language' => 'en',
                'CallBackUrl' => $request->getSchemeAndHttpHost() . '/payment/callback',
//                'ErrorUrl' => $request->getSchemeAndHttpHost() . '/payment-failed',
                'MobileCountryCode' => '+965',
                'SessionId' => $request->input('session_id'),
            ];
            Log::info('MyFatoorah SendPayment Data', $data);
            $response = $this->buildRequest('POST', '/ExecutePayment', $data);
            $responseData = $response->getData(true);
            Log::info('MyFatoorah SendPayment Response', $responseData);
            if (isset($responseData['success']) && $responseData['success'] &&
                isset($responseData['data']['Data']['PaymentURL'])) {
                Log::info('MyFatoorah Payment Created Successfully', $responseData['data']['Data']);
                return [
                    'success' => true,
                    'url' => $responseData['data']['Data']['PaymentURL'],
                    'invoice_id' => $responseData['data']['Data']['InvoiceId'] ?? null,
                    'payment_id' => $this->extractPaymentId($responseData['data']['Data']['PaymentURL'])
                ];
            }
            Log::error('MyFatoorah Payment Creation Failed', $responseData);
            return [
                'success' => false,
                'url' => route('payment.failed'),
                'error' => $responseData['message'] ?? 'Payment creation failed'
            ];
        } catch (\Exception $e) {
            Log::error('MyFatoorah SendPayment Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'url' => route('payment.failed'),
                'error' => $e->getMessage()
            ];
        }
    }


    public function callBack(Request $request): bool
    {
        Log::info('MyFatoorah Callback Received', $request->all());
        try {
            $paymentId = $request->input('paymentId');
            if (!$paymentId) {
                Log::error('MyFatoorah Callback: No paymentId provided');
                return false;
            }
            $data = [
                'KeyType' => 'paymentId',
                'Key' => $paymentId,
            ];
            Log::info('MyFatoorah GetPaymentStatus Request', $data);
            $response = $this->buildRequest('POST', '/getPaymentStatus', $data);
            $responseData = $response->getData(true);
            Log::info('MyFatoorah GetPaymentStatus Response', $responseData);
            // Store callback data for debugging
            Storage::put('myfatoorah_response.json', json_encode([
                'myfatoorah_callback_request' => $request->all(),
                'myfatoorah_response_status' => $responseData,
                'timestamp' => now()->toDateTimeString()
            ]));
            // Check if response is successful and payment is paid
            if (isset($responseData['success']) && $responseData['success'] &&
                isset($responseData['data']['Data']['InvoiceStatus']) &&
                $responseData['data']['Data']['InvoiceStatus'] === 'Paid') {
                Log::info('MyFatoorah Payment Confirmed as Paid');
                return true;
            }
            Log::info('MyFatoorah Payment Not Paid', [
                'status' => $responseData['data']['Data']['InvoiceStatus'] ?? 'Unknown'
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('MyFatoorah Callback Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function extractPaymentId(string $url): ?string
    {
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['query'])) {
            return null;
        }

        parse_str($parsedUrl['query'], $queryParams);
        return $queryParams['paymentId'] ?? null;
    }
}
