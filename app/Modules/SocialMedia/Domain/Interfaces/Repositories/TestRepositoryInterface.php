<?php

namespace App\Modules\SocialMedia\Domain\Interfaces\Repositories;

use App\Modules\SocialMedia\Domain\Entities\Test;

interface TestRepositoryInterface
{
    public function create(Test $test): Test;
    public function getAll(): array;
}