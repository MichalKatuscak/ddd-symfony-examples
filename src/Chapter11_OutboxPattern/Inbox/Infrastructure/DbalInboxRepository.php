<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Inbox\Infrastructure;

use App\Chapter11_OutboxPattern\Inbox\Application\InboxRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

/**
 * Inbox nad DBAL, jak ho ukazuje kniha (15.06). Zápis nepotřebuje Unit of
 * Work – jediným úkolem je vložit dvojici (eventId, consumer) pod unikátním
 * indexem.
 *
 * Tabulku `inbox` ukázka migrací nezakládá; za běhu stránky používá
 * InMemoryInboxRepository. Tuhle třídu ověřuje test nad SQLite v paměti
 * (tests/Chapter11/Inbox/DbalInboxRepositoryTest.php), včetně DDL.
 */
final readonly class DbalInboxRepository implements InboxRepository
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function isProcessed(Uuid $eventId, string $consumer): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM inbox WHERE event_id = :id AND consumer = :consumer',
            ['id' => (string) $eventId, 'consumer' => $consumer],
        );
    }

    public function markProcessed(Uuid $eventId, string $consumer): void
    {
        // UniqueConstraintViolationException se zde záměrně nechytá.
        // Při souběhu musí shodit celou transakci subscribera i s vedlejším
        // efektem; Messenger zprávu zopakuje a podruhé ji zastaví
        // isProcessed(). Spolknutá výjimka by na MySQL nechala commitnout
        // i duplicitní efekt a UNIQUE by přestal být pojistkou.
        $this->connection->insert('inbox', [
            'id'           => (string) Uuid::v7(),
            'event_id'     => (string) $eventId,
            'consumer'     => $consumer,
            'processed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }
}
