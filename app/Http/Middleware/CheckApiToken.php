<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedToken = $request->header('X-API-Token') ?? $request->header('Authorization');

        if ($providedToken && str_starts_with($providedToken, 'Bearer ')) {
            $providedToken = substr($providedToken, 7);
        }

        $expectedToken = config('app.api_token');

        if (empty($providedToken)) {
            return $this->unauthorizedResponse('API token is required. Please provide X-API-Token header.');
        }

        if (!hash_equals($expectedToken, $providedToken)) {
            return $this->unauthorizedResponse('Invalid API token provided.');
        }

        return $next($request);
    }

    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 401,
            'timestamp' => now()->toISOString(),
        ], 401);
    }
}
