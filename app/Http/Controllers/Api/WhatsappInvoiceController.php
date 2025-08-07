<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CreateInvoiceRequest;
use App\Http\Requests\ValidateWhastappRequest;
use App\Http\Resources\UserResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappInvoiceController extends BaseApiController
{
    public function validateWhatsapp(ValidateWhastappRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('phone', $data['phone'])->first();

        if (!$user?->phone) {
            return $this->unauthorizedResponse('User not found');
        }

        if (!preg_match('/^[\+]?[1-9][\d]{6,14}$/', $user->phone)) {
            return $this->errorResponse('The user phone number is not in valid international format.', 422);
        }

        return $this->successResponse([
            'user' => new UserResource($user),
        ], 'Valid WhatsApp Number');
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $invoice = Invoice::query()->create($data);
        $items = InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'price' => $invoice->total,
            'total' => $invoice->total,
        ]);
        return $this->successResponse($invoice, 'Invoice Created Successfully', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $phone = $request->input('phone');
        $user = User::where('phone', $phone)->first();
        $invoices = Invoice::query()->where('user_id', $user->id)->get();
        return $this->successResponse($invoices, 'Invoices Retrieved Successfully', 201);
    }
}
