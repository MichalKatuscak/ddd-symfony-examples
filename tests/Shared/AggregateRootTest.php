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

    public function test_pull_events_returns_empty_array_on_second_call(): void
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
        $aggregate->pullEvents(); // first call — consumes events

        $second = $aggregate->pullEvents();

        $this->assertSame([], $second);
    }

    public function test_multiple_events_recorded_in_order(): void
    {
        $aggregate = new class extends AggregateRoot {
            public function recordEvent(string $name): void
            {
                $eventName = $name;
                $this->record(new class($eventName) implements DomainEvent {
                    public function __construct(public readonly string $name) {}

                    public function occurredAt(): \DateTimeImmutable
                    {
                        return new \DateTimeImmutable();
                    }
                });
            }
        };

        $aggregate->recordEvent('first');
        $aggregate->recordEvent('second');
        $aggregate->recordEvent('third');

        $events = $aggregate->pullEvents();

        $this->assertCount(3, $events);
        $this->assertSame('first', $events[0]->name);
        $this->assertSame('second', $events[1]->name);
        $this->assertSame('third', $events[2]->name);
    }
}
