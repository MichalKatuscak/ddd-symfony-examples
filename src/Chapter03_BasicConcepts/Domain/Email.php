<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain;

final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address: ' . $value);
        }
        $this->value = strtolower($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
