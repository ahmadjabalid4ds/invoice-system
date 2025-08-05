<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WhatsappInvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/validateWhatsapp', [WhatsappInvoiceController::class, 'validateWhatsapp']);
        Route::post('/storeInvoice', [WhatsappInvoiceController::class, 'store']);
    });
});
