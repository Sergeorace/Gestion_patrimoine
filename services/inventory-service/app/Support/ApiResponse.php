<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Reponses API uniformes : { data } / { error: { code, message, details } }
 */
trait ApiResponse
{
    protected function ok(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = ['data' => $data];
        if ($meta) {
            $payload['meta'] = $meta;
        }
        return response()->json($payload, $status);
    }

    protected function created(mixed $data = null): JsonResponse
    {
        return $this->ok($data, 201);
    }

    protected function error(string $code, string $message, int $status = 400, mixed $details = null): JsonResponse
    {
        return response()->json([
            'error' => array_filter([
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ]),
        ], $status);
    }
}
