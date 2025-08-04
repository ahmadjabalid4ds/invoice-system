<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Invoice;
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
        $invoice_id = $request->get('invoice_id');
        $invoice = Invoice::find($invoice_id);
        if (!$invoice || !$invoice->canBePaid()){
            Log::error('trying to pay twice');
            return [
                'success' => false,
                'url' => route('payment.failed'),
                'error' => $responseData['message'] ?? 'Payment creation failed'
            ];
        }
        Log::info('MyFatoorah SendPayment Request', $request->all());
        Log::info('invoiceeeeee', $invoice->toArray());


        try {
            $data = [
                'InvoiceValue' => $request->input('InvoiceValue'),
                'CustomerName' => $request->input('CustomerName'),
                'CustomerEmail' => $request->input('CustomerEmail'),
                'DisplayCurrencyIso' => $request->input('DisplayCurrencyIso', 'KWD'),
                'Language' => 'en',
                'CallBackUrl' => $request->getSchemeAndHttpHost() . '/payment/callback?invoice_id=' . $invoice_id ,
                'ErrorUrl' => $request->getSchemeAndHttpHost() . '/payment-failed',
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

    public function executePayment(array $data): array
    {
        try {
            $requestData = [
                'SessionId' => $data['SessionId'],
                'InvoiceValue' => (float) $data['InvoiceValue'],
                'CustomerName' => $data['CustomerName'],
                'DisplayCurrencyIso' => 'KWD',
                'MobileCountryCode' => '+965',
                'CustomerMobile' => $data['CustomerMobile'] ?? '12345678',
                'CustomerEmail' => $data['CustomerEmail'] ?? 'customer@example.com',
                'CallBackUrl' => url('/payment/callback'),
                'ErrorUrl' => url('/payment/failed'),
                'Language' => 'EN',
                'CustomerReference' => 'INV-' . ($data['invoice_id'] ?? uniqid()),
            ];

            Log::info('MyFatoorah ExecutePayment Request', $requestData);
            $response = $this->buildRequest('POST', '/v2/ExecutePayment', $requestData);
            $responseData = $response->getData(true);
            Log::info('MyFatoorah ExecutePayment Response', $responseData);

            if (isset($responseData['success']) && $responseData['success']) {
                if (isset($responseData['data']['Data'])) {
                    $invoiceData = $responseData['data']['Data'];

                    if (isset($invoiceData['InvoiceStatus']) &&
                        in_array($invoiceData['InvoiceStatus'], ['Paid', 'DuePaid'])) {

                        return [
                            'success' => true,
                            'payment_id' => $invoiceData['InvoiceId'] ?? null,
                            'invoice_status' => $invoiceData['InvoiceStatus'],
                            'transaction_id' => isset($invoiceData['InvoiceTransactions'][0]) ?
                                $invoiceData['InvoiceTransactions'][0]['TransactionId'] : null
                        ];
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Payment executed successfully',
                    'data' => $responseData['data']
                ];
            }

            return [
                'success' => false,
                'error' => $responseData['message'] ?? 'Payment execution failed'
            ];

        } catch (\Exception $e) {
            Log::error('MyFatoorah ExecutePayment Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
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

            if (isset($responseData['success']) && $responseData['success'] &&
                isset($responseData['data']['Data']['InvoiceStatus']) &&
                $responseData['data']['Data']['InvoiceStatus'] === 'Paid') {

                Log::info('MyFatoorah Payment Confirmed as Paid');

                $this->updateInvoiceFromCallback($responseData['data']['Data'], $request->input('invoice_id'));

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

    private function updateInvoiceFromCallback(array $paymentData, string $invoiceId): void
    {
        try {
            $invoice = null;

            if (isset($paymentData['CustomerReference'])) {
                $invoiceId = str_replace('INV-', '', $paymentData['CustomerReference']);
                $invoice = Invoice::find($invoiceId);
            }

            if (!$invoice && $invoiceId) {
                $invoice = Invoice::find($invoiceId);
            }

            if ($invoice && !$invoice->isPaid()) {
                $paymentInfo = [
                    'payment_id' => $paymentData['InvoiceId'] ?? $invoiceId,
                    'transaction_id' => isset($paymentData['InvoiceTransactions'][0]) ?
                        $paymentData['InvoiceTransactions'][0]['TransactionId'] : null,
                    'gateway_response' => $paymentData
                ];

                $invoice->markAsPaid($paymentInfo);

                Log::info('Invoice updated from callback', [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $paymentInfo['payment_id']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update invoice from callback', [
                'error' => $e->getMessage(),
                'payment_id' => $invoiceId
            ]);
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
