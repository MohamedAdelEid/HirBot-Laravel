<?php

namespace App\Modules\SocialMedia\Domain\Entities;

class Test
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name
        ];
    }
}