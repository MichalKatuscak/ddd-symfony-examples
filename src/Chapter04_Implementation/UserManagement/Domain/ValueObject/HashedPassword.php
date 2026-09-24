<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

// Mapuje se přes #[ORM\Embedded], takže potřebuje atributy –
// stejně jako UserName.
#[ORM\Embeddable]
final readonly class HashedPassword
{
    private function __construct(
        #[ORM\Column(length: 255)]
        public string $value,
    ) {}

    public static function fromPlainText(string $plain): self
    {
        if (strlen($plain) < 12) {
            throw new \InvalidArgumentException('Password must be at least 12 characters');
        }

        return new self(password_hash($plain, PASSWORD_DEFAULT));
    }

    /** Rekonstituce z databáze – hash se znovu nehashuje. */
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function matches(string $plain): bool
    {
        return password_verify($plain, $this->value);
    }
}
