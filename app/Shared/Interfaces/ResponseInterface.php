<?php

namespace App\Shared\Interfaces;

use Illuminate\Http\JsonResponse;

interface ResponseInterface
{
    public function success(mixed $data = null, string $message = null, int $statusCode = 200): JsonResponse;

    public function error(string $message, mixed $errors = null, int $statusCode = 400): JsonResponse;

    public function paginated(mixed $data, ?string $message = null, int $statusCode = 200): JsonResponse;
}
