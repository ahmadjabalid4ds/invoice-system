<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('invoice/{id}', [InvoiceController::class, 'index'])->name('payment-page');
Route::post('payment', [InvoiceController::class, 'paymentProcess'])->name('payment');
Route::get('/payment-success', [InvoiceController::class, 'success'])->name('payment.success');
Route::get('/payment-failed', [InvoiceController::class, 'failed'])->name('payment.failed');
Route::match(['GET','POST'],'/payment/callback', [InvoiceController::class, 'callBack']);
