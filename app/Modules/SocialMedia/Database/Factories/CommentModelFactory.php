<?php

namespace App\Modules\SocialMedia\Database\Factories;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentModelFactory extends Factory
{
    protected $model = CommentModel::class;

    public function definition()
    {
        return [
            'user_id' => 'user' . $this->faker->randomNumber(3),
            'post_id' => function () {
                return PostModel::factory()->create()->id;
            },
            'parent_comment_id' => null,
            'content' => $this->faker->paragraph,
            'image_path' => $this->faker->optional(0.2)->passthrough('path/to/image/' . $this->faker->word . '.jpg'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
