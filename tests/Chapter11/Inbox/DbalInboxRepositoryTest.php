<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Inbox;

use App\Chapter11_OutboxPattern\Inbox\Infrastructure\DbalInboxRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * DbalInboxRepository nad SQLite v paměti. Tabulka odpovídá entitě
 * InboxMessage z knihy: surrogate PK a kompozitní UNIQUE (event_id, consumer).
 */
final class DbalInboxRepositoryTest extends TestCase
{
    private Connection $connection;
    private DbalInboxRepository $inbox;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement(
            'CREATE TABLE inbox (
                id           CHAR(36)    NOT NULL PRIMARY KEY,
                event_id     CHAR(36)    NOT NULL,
                consumer     VARCHAR(64) NOT NULL,
                processed_at DATETIME    NOT NULL
            )',
        );
        $this->connection->executeStatement(
            'CREATE UNIQUE INDEX uniq_inbox_event_consumer ON inbox (event_id, consumer)',
        );
        // Vedlejší efekt subscribera, který se nesmí zapsat dvakrát.
        $this->connection->executeStatement('CREATE TABLE side_effect (event_id CHAR(36) NOT NULL)');

        $this->inbox = new DbalInboxRepository($this->connection);
    }

    public function test_marked_event_is_reported_as_processed(): void
    {
        $eventId = Uuid::v7();

        self::assertFalse($this->inbox->isProcessed($eventId, 'reporting'));
        $this->inbox->markProcessed($eventId, 'reporting');
        self::assertTrue($this->inbox->isProcessed($eventId, 'reporting'));
        self::assertFalse($this->inbox->isProcessed($eventId, 'notifications'));
    }

    public function test_duplicate_mark_propagates_unique_violation(): void
    {
        $eventId = Uuid::v7();
        $this->inbox->markProcessed($eventId, 'reporting');

        $this->expectException(UniqueConstraintViolationException::class);
        $this->inbox->markProcessed($eventId, 'reporting');
    }

    public function test_concurrent_duplicate_rolls_back_side_effect(): void
    {
        $eventId = Uuid::v7();

        // Worker A zpracuje událost celou.
        $this->handle($eventId);

        // Worker B prošel kontrolou isProcessed() dřív, než A commitnul,
        // a teď provádí efekt i zápis do inboxu. UNIQUE ho musí shodit
        // i s efektem – kdyby markProcessed() výjimku spolkl, efekt by zůstal.
        try {
            $this->connection->transactional(function () use ($eventId): void {
                $this->connection->insert('side_effect', ['event_id' => (string) $eventId]);
                $this->inbox->markProcessed($eventId, 'reporting');
            });
            self::fail('Duplicitní zápis do inboxu musí skončit výjimkou.');
        } catch (UniqueConstraintViolationException) {
        }

        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM side_effect'));
    }

    private function handle(Uuid $eventId): void
    {
        $this->connection->transactional(function () use ($eventId): void {
            if ($this->inbox->isProcessed($eventId, 'reporting')) {
                return;
            }
            $this->connection->insert('side_effect', ['event_id' => (string) $eventId]);
            $this->inbox->markProcessed($eventId, 'reporting');
        });
    }
}
