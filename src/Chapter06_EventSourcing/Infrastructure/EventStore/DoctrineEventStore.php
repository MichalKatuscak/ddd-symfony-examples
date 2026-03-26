<?php
namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;
use App\Shared\Domain\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEventStore implements EventStoreInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function append(string $aggregateId, array $events): void
    {
        foreach ($events as $event) {
            $payload = $this->serializeEvent($event);
            $stored = new StoredEvent(
                $aggregateId,
                get_class($event),
                $payload,
                $event->occurredAt(),
            );
            $this->em->persist($stored);
        }
        $this->em->flush();
    }

    public function load(string $aggregateId): array
    {
        $stored = $this->em->getRepository(StoredEvent::class)
            ->findBy(['aggregateId' => $aggregateId]);

        return array_map(function (StoredEvent $s) {
            $class = $s->eventClass();
            $payload = $s->payload();
            $args = array_values($payload);
            // occurredAt is stored as ISO string, reconstruct DateTimeImmutable
            $last = end($args);
            if (is_string($last) && \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $last) !== false) {
                $args[count($args) - 1] = new \DateTimeImmutable($last);
            }
            return new $class(...$args);
        }, $stored);
    }

    private function serializeEvent(DomainEvent $event): array
    {
        $ref = new \ReflectionClass($event);
        $payload = [];
        foreach ($ref->getProperties() as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($event);
            if ($value instanceof \DateTimeImmutable) {
                $value = $value->format(\DateTimeInterface::ATOM);
            }
            $payload[$prop->getName()] = $value;
        }
        return $payload;
    }
}
