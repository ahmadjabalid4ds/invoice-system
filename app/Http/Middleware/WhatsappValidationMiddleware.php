<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WhatsappValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $data = json_decode($request->getContent(), true);

        $waPhone = $request->input('wa_number') ?? ($data['wa_number'] ?? null);
        $toWaPhone = $request->input('to_wa_number') ?? ($data['to_wa_number'] ?? null);

        // --- Validate wa_number ---
        if (empty($waPhone)) {
            return $this->errorResponse('Phone number (wa_number) is required', 400);
        }

        if (!$this->isValidPhoneFormat($waPhone)) {
            return $this->errorResponse('Invalid phone number format (wa_number). Please provide a valid international phone number (e.g., +966501234567)', 422);
        }

        $normalizedWaPhone = $this->normalizePhoneNumber($waPhone);

        $user = User::where('phone', $normalizedWaPhone)->first();

        if (!$user) {
            return $this->errorResponse('No user found with this phone number (wa_number)', 404);
        }

        if (method_exists($user, 'isActive') && !$user->isActive()) {
            return $this->errorResponse('User account (wa_number) is inactive', 403);
        }

        $request->merge([
            'validated_user' => $user,
            'normalized_phone' => $normalizedWaPhone
        ]);

        // --- Optionally validate to_wa_number ---
        if (!empty($toWaPhone)) {
            if (!$this->isValidPhoneFormat($toWaPhone)) {
                return $this->errorResponse('Invalid phone number format (to_wa_number). Please provide a valid international phone number.', 422);
            }

            $normalizedToWaPhone = $this->normalizePhoneNumber($toWaPhone);
            $toUser = User::where('phone', $normalizedToWaPhone)->first();
            if (!$toUser) {
                return $this->errorResponse('No user found with this phone number (wa_number)', 404);
            }
            $request->merge(['normalized_to_phone' => $normalizedToWaPhone]);
        }

        return $next($request);
    }

    /**
     * Validate phone number format
     */
    private function isValidPhoneFormat(string $phone): bool
    {
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);

        return preg_match('/^\+[1-9]\d{6,14}$/', $cleanPhone) ||
            preg_match('/^[1-9]\d{9,14}$/', $cleanPhone);
    }

    /**
     * Normalize phone number by removing + and leading 0s, and converting to international format
     */
    private function normalizePhoneNumber(string $phone): string
    {
        $normalized = preg_replace('/\D/', '', $phone);

        if (str_starts_with($normalized, '05')) {
            $normalized = '966' . substr($normalized, 1);
        }

        return $normalized;
    }

    /**
     * Standardized error response
     */
    private function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $statusCode,
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }
}
