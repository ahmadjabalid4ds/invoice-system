<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CreateInvoiceRequest;
use App\Http\Requests\ValidateWhastappRequest;
use App\Http\Resources\UserResource;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\JsonResponse;

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
        return $this->successResponse($invoice, 'Invoice Created Successfully', 201);
    }

    public function index(): JsonResponse
    {
        $invoices = Invoice::query()->where('user_id', auth()->user()?->id)->get();
        return $this->successResponse($invoices, 'Invoices Retrieved Successfully', 201);
    }
}
