<?php

namespace App\Modules\SocialMedia\Application\DTOs;

class TestDto
{
    public function __construct(
        public readonly string $name
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name']
        );
    }
}