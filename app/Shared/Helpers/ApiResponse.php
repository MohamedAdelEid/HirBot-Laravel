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
        return response()->json([
            'status' => ResponseStatus::SUCCESS->value,
            'message' => $message ?? 'Data retrieved successfully',
            'data' => [
                'data' => $this->transformData($data->items()),
                'currentPage' => $data->currentPage(),
                'totalPages' => $data->lastPage(),
                'pageSize' => $data->perPage(),
                'totalRecords' => $data->total(),
            ],
            'statusCode' => $statusCode
        ], $statusCode);
    }

    /**
     * Create a cursor-paginated response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function cursorPaginated(mixed $data, ?string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => ResponseStatus::SUCCESS->value,
            'message' => $message,
            'data' => [
                'data' => $this->transformData($data->items()),
                'nextPageCursor' => optional($data->last())?->CreationDate?->toIso8601String(),
                'pageSize' => $data->perPage(),
                'hasMore' => $data->hasMorePages(),
                'totalRecords' => $data->total(),
            ],
            'statusCode' => $statusCode,
        ], $statusCode);
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
            return $data->toArray(request());
        }

        return $data;
    }
}
