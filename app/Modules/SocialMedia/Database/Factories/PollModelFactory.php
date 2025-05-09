<?php

namespace App\Modules\SocialMedia\Database\Factories;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class PollModelFactory extends Factory
{
    protected $model = PollModel::class;

    public function definition()
    {
        return [
            'post_id' => function () {
                return PostModel::factory()->create()->id;
            },
            'question' => $this->faker->sentence . '?',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
