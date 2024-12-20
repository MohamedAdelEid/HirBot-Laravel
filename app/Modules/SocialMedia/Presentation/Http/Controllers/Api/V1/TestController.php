<?php

namespace app\Modules\SocialMedia\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\SocialMedia\Domain\Interfaces\Services\TestServiceInterface;
use App\Modules\SocialMedia\Application\DTOs\TestDto;
use App\Modules\SocialMedia\Presentation\Http\Requests\CreateTestRequest;
use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    public function __construct(
        private TestServiceInterface $testService
    ) {
    }

    public function create(CreateTestRequest $request): JsonResponse
    {
        $dto = TestDto::fromArray($request->validated());
        $test = $this->testService->create($dto);

        return response()->json([
            'data' => $test->toArray()
        ], 201);
    }

    public function index(): JsonResponse
    {
        $tests = $this->testService->getAll();

        return response()->json([
            env('APP_NAME') => array_map(fn($test) => $test->toArray(), $tests)
        ]);
    }
}