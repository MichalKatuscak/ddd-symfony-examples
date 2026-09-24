<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventSourcing;

use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;

/**
 * Event Store nad DBAL. Konflikt verzí hlídá unikátní index
 * (aggregate_id, version); výjimku z něj překládá na ConcurrencyException.
 *
 * Tabulka má prefix ch06_, protože databázi ukázek sdílejí všechny kapitoly.
 * Kniha ji jmenuje event_store a DDL píše pro MySQL; ukázka běží na SQLite
 * (migrace Version20260924120000).
 */
final class DoctrineEventStore implements EventStore
{
    public const TABLE = 'ch06_event_store';

    public function __construct(
        private readonly Connection $connection,
        private readonly EventSerializer $serializer,
        private readonly EventMetadataProvider $metadata,
    ) {}

    /**
     * @param list<DomainEvent> $events
     */
    public function append(
        string $aggregateId,
        string $aggregateType,
        array $events,
        int $expectedVersion,
    ): void {
        $version = $expectedVersion;

        $this->connection->beginTransaction();

        try {
            foreach ($events as $event) {
                $version++;

                $this->connection->insert(self::TABLE, [
                    'event_id'       => $event->eventId,
                    'aggregate_id'   => $aggregateId,
                    'aggregate_type' => $aggregateType,
                    'event_type'     => $event->eventType(),
                    'payload'        => json_encode($event->toPayload(), JSON_THROW_ON_ERROR),
                    // Korelační a kauzální ID nese kontext requestu; bez nich
                    // je sloupec metadata jen mrtvé místo v tabulce.
                    'metadata'       => json_encode(
                        $this->metadata->forEvent($event),
                        JSON_THROW_ON_ERROR,
                    ),
                    'schema_version' => $event->schemaVersion(),
                    'version'        => $version,
                    'occurred_on'    => $event->occurredAt->format('Y-m-d H:i:s.u'),
                ]);
            }

            $this->connection->commit();
        } catch (UniqueConstraintViolationException $e) {
            $this->connection->rollBack();
            throw new ConcurrencyException(
                "Concurrency conflict for aggregate {$aggregateId} at version {$version}.",
                previous: $e,
            );
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return list<EventEnvelope>
     */
    public function loadStream(string $aggregateId, int $fromVersion = 1): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT event_type, payload, schema_version, version, occurred_on
               FROM ' . self::TABLE . '
              WHERE aggregate_id = :aggregateId
                AND version >= :fromVersion
           ORDER BY version ASC',
            ['aggregateId' => $aggregateId, 'fromVersion' => $fromVersion],
        );

        return array_map(
            fn (array $row) => $this->serializer->deserialize($row),
            $rows,
        );
    }

    /**
     * Iteruje přes celý Event Store v dávkách – paměťově efektivní pro rebuild projekcí.
     *
     * @return \Generator<EventEnvelope>
     */
    public function loadAll(int $batchSize = 500): \Generator
    {
        $lastId = 0;

        do {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, event_type, payload, schema_version, version, occurred_on
                   FROM ' . self::TABLE . '
                  WHERE id > :lastId
               ORDER BY id ASC
                  LIMIT :limit',
                ['lastId' => $lastId, 'limit' => $batchSize],
                // Bez explicitního typu DBAL naváže limit jako řetězec
                // a MySQL výraz LIMIT '500' odmítne.
                ['limit' => ParameterType::INTEGER],
            );

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                yield $this->serializer->deserialize($row);
            }
        } while (count($rows) === $batchSize);
    }
}
