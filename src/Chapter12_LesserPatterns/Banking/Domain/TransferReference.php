<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Banking\Domain;

use Symfony\Component\Uid\Uuid;

/**
 * Identifikace jednoho převodu. Oba pohyby (výběr i vklad) nesou tutéž
 * referenci, takže se dají dohledat jako pár.
 */
final readonly class TransferReference
{
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('TransferReference must be a valid UUID');
        }
    }

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }
}
