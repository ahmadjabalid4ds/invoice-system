<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CreateInvoiceRequest;
use App\Http\Resources\UserResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class WhatsappInvoiceController extends BaseApiController
{
    public function validateWhatsapp(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorizedResponse('User not authenticated');
        }

        if (!$user->phone) {
            return $this->unauthorizedResponse('User does not have a phone number');
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
}
