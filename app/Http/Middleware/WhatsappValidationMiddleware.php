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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get phone number from request
        $phone = $request->input('phone');

        // Check if phone number is provided
        if (empty($phone)) {
            return $this->errorResponse('Phone number is required', 400);
        }

        // Validate phone number format
        if (!$this->isValidPhoneFormat($phone)) {
            return $this->errorResponse('Invalid phone number format. Please provide a valid international phone number (e.g., +966501234567)', 422);
        }

        // Normalize phone number for database lookup
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        // Check if user exists with this phone number
        $user = User::where('phone', $normalizedPhone)
            ->orWhere('phone', $phone)
            ->orWhere('phone', ltrim($phone, '+'))
            ->first();

        if (!$user) {
            return $this->errorResponse('No user found with this phone number', 404);
        }

        // Check if user is active (optional - add this if you have user status)
        if (method_exists($user, 'isActive') && !$user->isActive()) {
            return $this->errorResponse('User account is inactive', 403);
        }

        // Add validated user to request for use in controller
        $request->merge(['validated_user' => $user]);
        $request->merge(['normalized_phone' => $normalizedPhone]);

        return $next($request);
    }

    /**
     * Validate phone number format
     */
    private function isValidPhoneFormat(string $phone): bool
    {
        // Remove all non-digit characters except + for initial check
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);

        // Basic validation rules:
        // 1. Should start with + or be 10-15 digits
        // 2. Should contain only digits after country code
        // 3. Should be between 10-15 digits total (international standard)

        // Check if it starts with + and has 7-15 digits after
        if (preg_match('/^\+[1-9]\d{6,14}$/', $cleanPhone)) {
            return true;
        }

        // Check if it's a local number with 10-15 digits
        if (preg_match('/^[1-9]\d{9,14}$/', $cleanPhone)) {
            return true;
        }

        // Saudi Arabia specific validation (if needed)
        // Uncomment if you want to specifically validate Saudi numbers
        // if (preg_match('/^\+966[5][0-9]{8}$/', $cleanPhone)) {
        //     return true;
        // }

        return false;
    }

    /**
     * Normalize phone number for consistent storage/lookup
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $normalized = preg_replace('/[^\d+]/', '', $phone);

        // If it doesn't start with +, and it's a Saudi number starting with 05, convert to +966
        if (!str_starts_with($normalized, '+') && str_starts_with($normalized, '05')) {
            $normalized = '+966' . substr($normalized, 1);
        }

        // If it doesn't start with + but looks like a complete international number, add +
        if (!str_starts_with($normalized, '+') && strlen($normalized) > 10) {
            $normalized = '+' . $normalized;
        }

        return $normalized;
    }

    /**
     * Return standardized error response
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
