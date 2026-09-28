<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Domain\ValueObject;

final readonly class VerificationToken
{
    private function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Verification token must not be empty.');
        }
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(32)));
    }

    // Přijímá i tokeny vydané legacy systémem; formát proto nevynucuje.
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        // Token je tajemství: porovnání v konstantním čase brání timing útoku.
        return hash_equals($this->value, $other->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
