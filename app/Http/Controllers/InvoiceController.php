<?php

namespace App\Http\Controllers;

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
        if (!$invoice) {
            abort(404, 'Invoice not found');
        }
        try {
//            $myfatoorah = new Myfatoorah();
//            $sessionData = $myfatoorah->initiateSession();
//
//            $country_code = $sessionData['country_code'];
//            $session_id = $sessionData['session_id'];

            return view('pay', compact( 'invoice'));
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Failed to initiate MyFatoorah session: ' . $e->getMessage());
            return back()->with('error', 'Unable to initialize payment. Please try again.');
        }
    }

    public function paymentProcess(Request $request)
    {
        // Validate the request
        $request->validate([
            'InvoiceValue' => 'required|numeric|min:1',
            'CustomerName' => 'required|string|max:255',
            'CustomerEmail' => 'required|email',
            'DisplayCurrencyIso' => 'required|in:EGP,USD,EUR,SAR,KWD,BHD,QAR,AED'
        ]);

        try {
            Log::info('Payment Process Started:', $request->all());

            $result = $this->paymentGateway->sendPayment($request);

            if ($result['success']) {
                Log::info('Payment URL Generated Successfully:', $result);
                return redirect($result['url']);
            } else {
                Log::error('Payment Process Failed:', $result);
                return redirect()->route('payment.failed')
                    ->with('error', $result['error'] ?? 'Payment initialization failed');
            }

        } catch (\Exception $e) {
            Log::error('Payment Process Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.failed')
                ->with('error', 'An error occurred while processing payment');
        }
    }

    public function callBack(Request $request)
    {
        try {
            Log::info('Payment Callback Received:', $request->all());

            $isPaymentSuccessful = $this->paymentGateway->callBack($request);

            if ($isPaymentSuccessful) {
                Log::info('Payment Callback: Payment Successful');
                return redirect()->route('payment.success')
                    ->with('success', 'Payment completed successfully');
            } else {
                Log::info('Payment Callback: Payment Failed or Pending');
                return redirect()->route('payment.failed')
                    ->with('error', 'Payment was not successful');
            }

        } catch (\Exception $e) {
            Log::error('Payment Callback Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.failed')
                ->with('error', 'An error occurred while processing payment callback');
        }
    }

    public function success()
    {

        return response()->json('success');
    }
    public function failed()
    {

        return response()->json('failed');
    }
}
