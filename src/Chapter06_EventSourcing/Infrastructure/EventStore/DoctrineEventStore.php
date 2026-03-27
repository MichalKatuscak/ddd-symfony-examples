<?php

declare(strict_types=1);
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
            ->findBy(['aggregateId' => $aggregateId], ['id' => 'ASC']);

        return array_map(function (StoredEvent $s) {
            $class = $s->eventClass();
            $payload = $s->payload();
            $ref = new \ReflectionClass($class);
            $args = [];
            foreach ($ref->getConstructor()->getParameters() as $param) {
                $name = $param->getName();
                $value = $payload[$name];
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType && $type->getName() === \DateTimeImmutable::class) {
                    $value = new \DateTimeImmutable($value);
                }
                $args[$name] = $value;
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
