<?php

namespace Database\Factories;

use App\Shared\Enums\UserRoleEnum;
use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Shared\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Id' => Str::uuid(),
            'FullName' => $this->faker->name(),
            'role' => $this->faker->randomElement(UserRoleEnum::values()),
            'EmailConfirmed' => $this->faker->boolean(),
            'IsVerified' => $this->faker->boolean(),
            'PhoneNumberConfirmed' => $this->faker->boolean(),
            'TwoFactorEnabled' => $this->faker->boolean(),
            'LockoutEnabled' => $this->faker->boolean(),
            'AccessFailedCount' => $this->faker->numberBetween(0, 5),
        ];
    }

    // /**
    //  * Indicate that the model's email address should be unverified.
    //  */
    // public function unverified(): static
    // {
    //     return $this->state(fn (array $attributes) => [
    //         'email_verified_at' => null,
    //     ]);
    // }
}
