<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Domain\Entities\Test;
use App\Modules\SocialMedia\Domain\Interfaces\Repositories\TestRepositoryInterface;
use App\Modules\SocialMedia\Domain\Interfaces\Services\TestServiceInterface;
use App\Modules\SocialMedia\Application\DTOs\TestDto;

class TestService implements TestServiceInterface
{
    public function __construct(
        private TestRepositoryInterface $testRepository
    ) {}

    public function create(TestDto $dto): Test
    {
        $test = new Test($dto->name);
        return $this->testRepository->create($test);
    }

    public function getAll(): array
    {
        return $this->testRepository->getAll();
    }
}