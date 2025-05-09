<?php

namespace App\Modules\SocialMedia\Database\Factories;

use App\Modules\SocialMedia\Domain\Enums\Post\PostMediaTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostMediaModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostMediaModelFactory extends Factory
{
    protected $model = PostMediaModel::class;

    public function definition()
    {
        return [
            'post_id' => function () {
                return PostModel::factory()->create()->id;
            },
            'type' => $this->faker->randomElement(PostMediaTypeEnum::values()),
            'media_url' => 'path/to/' . $this->faker->word . '.' . $this->faker->fileExtension(),
            'poster_url' => $this->faker->optional(0.3)->passthrough('path/to/poster/' . $this->faker->word . '.jpg'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

