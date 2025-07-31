<?php

namespace App\Utils;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Myfatoorah
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.my_fatoorah.api_key');
        $this->baseUrl = config('services.my_fatoorah.base_url');

        if (!$this->apiKey || !$this->baseUrl) {
            throw new \Exception('MyFatoorah API credentials not configured');
        }
    }

    public function initiateSession()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/InitiateSession', [
                'CustomerIdentifier' => 123,
                'SaveToken' => false
            ]);

            if (!$response->successful()) {
                Log::error('MyFatoorah InitiateSession failed', [
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to initiate session with MyFatoorah');
            }

            $data = $response->json();

            if ($data && $data['IsSuccess']) {
                return [
                    'country_code' => $data['Data']['CountryCode'],
                    'session_id' => $data['Data']['SessionId']
                ];
            } else {
                Log::error('MyFatoorah session initiation failed', $data);
                throw new \Exception('MyFatoorah session initiation failed: ' . ($data['Message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Exception in initiateSession: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPaymentStatus(string $paymentId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'GetPaymentStatus', [
                'KeyType' => 'PaymentId',
                'Key' => $paymentId,
            ]);

            if (!$response->successful()) {
                Log::error('MyFatoorah GetPaymentStatus failed', [
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to get payment status from MyFatoorah');
            }

            $data = $response->json();

            if ($data && $data['IsSuccess']) {
                return $data['Data'];
            } else {
                Log::error('MyFatoorah payment status check failed', $data);
                throw new \Exception('Payment status check failed: ' . ($data['Message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Exception in getPaymentStatus: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPaymentStatusBySessionId(string $sessionId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'GetPaymentStatus', [
                'KeyType' => 'InvoiceId',
                'Key' => $sessionId,
            ]);

            if (!$response->successful()) {
                logger()->info('responseeeeee:'. $response);
                Log::error('MyFatoorah GetPaymentStatus by SessionId failed', [
                    'response' => $response->body(),
                    'status' => $response->status(),
                    'session_id' => $sessionId
                ]);
                throw new \Exception('Failed to get payment status by session ID from MyFatoorah');
            }

            $data = $response->json();

            if ($data && $data['IsSuccess']) {
                return $data['Data'];
            } else {
                Log::error('MyFatoorah payment status check by session ID failed', $data);
                throw new \Exception('Payment status check failed: ' . ($data['Message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Exception in getPaymentStatusBySessionId: ' . $e->getMessage());
            throw $e;
        }
    }

    public function executePayment(array $paymentData)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'ExecutePayment', $paymentData);

            if (!$response->successful()) {
                Log::error('MyFatoorah ExecutePayment failed', [
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to execute payment with MyFatoorah');
            }

            $data = $response->json();

            if ($data && $data['IsSuccess']) {
                return $data['Data'];
            } else {
                Log::error('MyFatoorah payment execution failed', $data);
                throw new \Exception('Payment execution failed: ' . ($data['Message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Exception in executePayment: ' . $e->getMessage());
            throw $e;
        }
    }
}
