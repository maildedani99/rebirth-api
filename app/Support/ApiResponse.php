<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function ok(mixed $data = null, string $message = 'OK', array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
            'meta'    => !empty($meta) ? $meta : null,
        ], 200);
    }

    public static function created(mixed $data = null, string $message = 'Created', array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
            'meta'    => !empty($meta) ? $meta : null,
        ], 201);
    }

    public static function error(string $message = 'Error', int $status = 400, array|null $errors = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
            'meta'    => !empty($meta) ? $meta : null,
        ], $status);
    }

    public static function notFound(string $message = 'Not Found'): JsonResponse
    {
        return self::error($message, 404);
    }
}
