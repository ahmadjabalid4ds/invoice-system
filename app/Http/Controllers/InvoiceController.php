<?php

namespace App\Http\Controllers;

use App\Utils\Myfatoorah;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index($id){

        $invoice = Invoice::where('token','=',$id)->first();
        if (!$invoice) {
            abort(404, 'Invoice not found');
        }
        $data = new Myfatoorah();
        $comp = $data->initiateSession();
        $country_code = $comp['country_code'];
        $session_id = $comp['session_id'];

        return view('pay', compact('session_id','country_code' ,'invoice'));
    }
    public function payment(Request $request){
        Log::info('Payment request received');
        Log::info($request->all());
       return response()->json(['message' => 'Payment request received']);
    }
}
