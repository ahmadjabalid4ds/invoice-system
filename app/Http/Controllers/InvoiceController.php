<?php

namespace App\Http\Controllers;

use App\Utils\Myfatoorah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            abort(404, 'Invoice not found');
        }

        try {
            $myfatoorah = new Myfatoorah();
            $sessionData = $myfatoorah->initiateSession();

            $country_code = $sessionData['country_code'];
            $session_id = $sessionData['session_id'];

            return view('pay', compact('session_id', 'country_code', 'invoice'));
        } catch (\Exception $e) {
            Log::error('Failed to initiate MyFatoorah session: ' . $e->getMessage());
            return back()->with('error', 'Unable to initialize payment. Please try again.');
        }
    }

    public function payment(Request $request)
    {
        // TODO: figure out how my fatoorah works
        Log::info('Payment callback received', $request->all());

        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
//            'session_id' => 'required|string',
            'payment_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            logger()->info($validator->errors());
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoice = Invoice::where('id', $request->invoice_id)->firstOrFail();

            // Check if the invoice is already paid
            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice already paid'
                ], 200);
            }

            // Verify payment status with MyFatoorah
            $myfatoorah = new Myfatoorah();
            $paymentStatus = $myfatoorah->getPaymentStatusBySessionId($invoice->id);

            if ($paymentStatus && $paymentStatus['InvoiceStatus'] === 'Paid') {
                // Update invoice status
                $invoice->update([
                    'status' => 'paid',
                    'payment_type' => $request->payment_type,
                    'payment_id' => $paymentStatus['InvoiceId'] ?? null,
                    'transaction_id' => $paymentStatus['PaymentId'] ?? null,
                    'paid_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment recorded successfully',
                    'redirect_url' => route('payment-success', ['invoice' => $invoice->id])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Payment processing failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed'
            ], 500);
        }
    }

    public function success($invoice)
    {
        $invoice = Invoice::findOrFail($invoice);
        return view('payment-success', compact('invoice'));
    }

    public function failed(Request $request)
    {
        $invoiceId = $request->get('invoice_id');
        $invoice = null;

        if ($invoiceId) {
            $invoice = Invoice::where('token', $invoiceId)->first();
        }

        return view('payment-failed', compact('invoice'));
    }
}
