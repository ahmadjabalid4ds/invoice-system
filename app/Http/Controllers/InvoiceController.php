<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatusEnum;
use App\Interfaces\PaymentGatewayInterface;
use App\Utils\Myfatoorah;
use Illuminate\Http\Request;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{

    protected PaymentGatewayInterface $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }
    public function index($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice || !$invoice->canBePaid()) {
            abort(404, 'Invoice not found');
        }
        try {
            $myfatoorah = new Myfatoorah();
            $sessionData = $myfatoorah->initiateSession();

            $country_code = $sessionData['country_code'];
            $session_id = $sessionData['session_id'];

            return view('pay', compact( 'invoice', 'session_id', 'country_code'));
        } catch (\Exception $e) {
            dd($e->getMessage());
            return back()->with('error', 'Unable to initialize payment. Please try again.');
        }
    }

    public function paymentProcess(Request $request)
    {
        Log::info('Payment Process Started', $request->all());

        try {
            $result = $this->paymentGateway->sendPayment($request);

            if ($result['success']) {
                Log::info('Payment URL Generated Successfully', $result);
                return response()->json(['redirect_url' => $result['url']]);
            } else {
                Log::error('Payment Process Failed', $result);
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Payment initialization failed'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Payment Process Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing payment'
            ], 500);
        }
    }

    public function callBack(Request $request)
    {
        Log::info('=== CALLBACK RECEIVED ===');
        Log::info('Request Method: ' . $request->method());
        Log::info('Request URL: ' . $request->fullUrl());
        Log::info('Request Data', $request->all());

        try {
            $paymentId = $request->input('paymentId') ?? $request->input('Id');

            if (!$paymentId) {
                Log::error('No paymentId found in callback request');
                return response()->json(['status' => 'error', 'message' => 'No payment ID provided'], 400);
            }

            Log::info('Processing payment callback for ID: ' . $paymentId);

            $isPaymentSuccessful = $this->paymentGateway->callBack($request);

            if ($isPaymentSuccessful) {
                Log::info('Payment Callback: Payment Successful');

                if ($request->expectsJson() || $request->isMethod('POST')) {
                    return response()->json(['status' => 'success', 'message' => 'Payment verified']);
                }

                return redirect()->route('payment.success')
                    ->with('success', 'Payment completed successfully');
            } else {
                Log::info('Payment Callback: Payment Failed or Pending');

                if ($request->expectsJson() || $request->isMethod('POST')) {
                    return response()->json(['status' => 'failed', 'message' => 'Payment not verified'], 400);
                }

                return redirect()->route('payment.failed')
                    ->with('error', 'Payment was not successful');
            }
        } catch (\Exception $e) {
            Log::error('Payment Callback Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->isMethod('POST')) {
                return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
            }

            return redirect()->route('payment.failed')
                ->with('error', 'An error occurred while processing payment callback');
        }
    }

    public function success(Request $request)
    {
        $invoice_id = $request->get('invoice');
        $invoice = null;

        if ($invoice_id) {
            $invoice = Invoice::find($invoice_id);
        }

        return view('payment.success', compact('invoice'));
    }

    public function failed(Request $request)
    {
        return view('payment.failed');
    }
}
