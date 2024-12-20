<?php

namespace App\Modules\SocialMedia\Domain\Interfaces\Services;

use App\Modules\SocialMedia\Domain\Entities\Test;
use App\Modules\SocialMedia\Application\DTOs\TestDto;

interface TestServiceInterface
{
    public function create(TestDto $dto): Test;
    public function getAll(): array;
}