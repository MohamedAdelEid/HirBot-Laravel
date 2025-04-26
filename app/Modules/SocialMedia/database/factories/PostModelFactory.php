<?php

namespace App\Modules\SocialMedia\Database\Factories;

use App\Modules\SocialMedia\Domain\Enums\Post\PrivacyCommentsEnum;
use App\Modules\SocialMedia\Domain\Enums\Post\PostVisibilityEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostModelFactory extends Factory
{
    protected $model = PostModel::class;

    public function definition()
    {
        return [
            'user_id' => 'user' . $this->faker->randomNumber(3),
            'content' => $this->faker->paragraph,
            'privacy_comments' => $this->faker->randomElement(PrivacyCommentsEnum::values()),
            'visibility' => $this->faker->randomElement(PostVisibilityEnum::values()),
            // 'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

