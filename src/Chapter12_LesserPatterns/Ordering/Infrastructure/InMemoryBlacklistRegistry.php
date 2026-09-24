<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Infrastructure;

use App\Chapter12_LesserPatterns\Ordering\Application\BlacklistRegistry;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/** Ručně vedený seznam ukázky: jeden zákazník, kterého fraud detection zablokovala. */
#[AsAlias(id: BlacklistRegistry::class)]
final class InMemoryBlacklistRegistry implements BlacklistRegistry
{
    public const BLOCKED_CUSTOMER = '01920000-0000-7000-8000-00000000b10c';

    public function all(): array
    {
        return [CustomerId::fromString(self::BLOCKED_CUSTOMER)];
    }
}
