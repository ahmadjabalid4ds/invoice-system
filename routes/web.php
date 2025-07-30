<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('invoice/{id}', [InvoiceController::class, 'index'])->name('payment-page');
Route::post('payment', [InvoiceController::class, 'payment'])->name('payment');
Route::get('payment/success/{invoice}', [InvoiceController::class, 'success'])->name('payment-success');
Route::get('payment/failed', [InvoiceController::class, 'failed'])->name('payment-failed');
