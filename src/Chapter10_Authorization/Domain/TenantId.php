<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Domain;

final readonly class TenantId
{
    public function __construct(
        public string $value,
    ) {
        if ($value === '') {
            throw new \InvalidArgumentException('TenantId must not be empty.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
