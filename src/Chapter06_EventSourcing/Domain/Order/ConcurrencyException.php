<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Domain\Order;

final class ConcurrencyException extends \RuntimeException
{
    public static function versionMismatch(string $aggregateId, int $expected, int $actual): self
    {
        return new self(sprintf(
            'Concurrency conflict for aggregate %s: expected version %d, actual %d',
            $aggregateId,
            $expected,
            $actual,
        ));
    }
}
