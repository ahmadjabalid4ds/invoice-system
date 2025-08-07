<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WhatsappInvoiceController;
use App\Http\Middleware\WhatsappValidationMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');

        Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::prefix('whatsapp')->middleware(WhatsappValidationMiddleware::class)->group(function () {
        Route::get('index', [WhatsappInvoiceController::class, 'index']);
        Route::post('store-invoice', [WhatsappInvoiceController::class, 'store']);
        Route::post('validate-whatsapp', [WhatsappInvoiceController::class, 'validateWhatsapp']);
    });
});
