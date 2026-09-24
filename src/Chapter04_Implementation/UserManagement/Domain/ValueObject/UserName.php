<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class UserName
{
    public const MIN_LENGTH = 2;
    public const MAX_LENGTH = 100;

    #[ORM\Column(type: 'string', length: self::MAX_LENGTH)]
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        $length = mb_strlen($trimmed);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf(
                'Jméno musí mít %d–%d znaků (zadáno %d).',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
                $length,
            ));
        }

        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
