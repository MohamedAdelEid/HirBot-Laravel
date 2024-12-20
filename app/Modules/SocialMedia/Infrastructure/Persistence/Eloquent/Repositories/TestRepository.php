<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\SocialMedia\Domain\Entities\Test;
use App\Modules\SocialMedia\Domain\Interfaces\Repositories\TestRepositoryInterface;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\TestModel;

class TestRepository implements TestRepositoryInterface
{
    public function create(Test $test): Test
    {
        $testModel = TestModel::create([
            'name' => $test->getName()
        ]);

        return new Test($testModel->name);
    }

    public function getAll(): array
    {
        return TestModel::all()
            ->map(fn ($model) => new Test($model->name))
            ->toArray();
    }
}