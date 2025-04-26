<?php

namespace App\Modules\SocialMedia\Database\Factories;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollOptionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class PollOptionModelFactory extends Factory
{
    protected $model = PollOptionModel::class;

    public function definition()
    {
        return [
            'poll_id' => function () {
                return PollModel::factory()->create()->id;
            },
            'content' => $this->faker->sentence,
            'vote_count' => $this->faker->numberBetween(0, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
