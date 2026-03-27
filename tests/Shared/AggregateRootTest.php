<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\DomainEvent;
use PHPUnit\Framework\TestCase;

final class AggregateRootTest extends TestCase
{
    public function test_records_and_pulls_domain_events(): void
    {
        $aggregate = new class extends AggregateRoot {
            public function doSomething(): void
            {
                $this->record(new class implements DomainEvent {
                    public function occurredAt(): \DateTimeImmutable
                    {
                        return new \DateTimeImmutable();
                    }
                });
            }
        };

        $aggregate->doSomething();
        $events = $aggregate->pullEvents();

        $this->assertCount(1, $events);
        $this->assertEmpty($aggregate->pullEvents());
    }
}
