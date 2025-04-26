<?php

namespace App\Modules\SocialMedia\Database\Factories;

use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractionTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class InteractionModelFactory extends Factory
{
    protected $model = InteractionModel::class;

    public function definition()
    {
        return [
            'user_id' => 'user' . $this->faker->randomNumber(3),
            'interactable_id' => function () {
                return PostModel::factory()->create()->id;
            },
            'interactable_type' => 'post',
            'type' => $this->faker->randomElement(InteractionTypeEnum::values()),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

