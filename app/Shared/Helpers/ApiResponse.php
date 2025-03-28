<?php

namespace App\Shared\Helpers;

use App\Shared\Enums\ResponseStatus;
use App\Shared\Interfaces\ResponseInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse implements ResponseInterface
{
    /**
     * Create a success response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function success(mixed $data = null, string $message = null, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => ResponseStatus::SUCCESS->value,
            'message' => $message ?? 'Operation completed successfully',
            'data' => $this->transformData($data),
            'statusCode' => $statusCode
        ], $statusCode);
    }

    /**
     * Create an error response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return JsonResponse
     */
    public function error(string $message, mixed $errors = null, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status' => ResponseStatus::ERROR->value,
            'message' => $message,
            'errors' => $errors,
            'statusCode' => $statusCode
        ], $statusCode);
    }

    /**
     * Create a paginated response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function paginated(mixed $data, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        if ($data instanceof LengthAwarePaginator) {
            return response()->json([
                'status' => ResponseStatus::SUCCESS->value,
                'message' => $message ?? 'Data retrieved successfully',
                'data' => $this->transformData($data->items()),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total()
                ],
                'statusCode' => $statusCode
            ], $statusCode);
        }

        return $this->success($data, $message, $statusCode);
    }

    /**
     * Transform the data if it's a JsonResource
     *
     * @param mixed $data
     * @return mixed
     */
    private function transformData(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->response()->getData(true);
        }

        return $data;
    }
}
