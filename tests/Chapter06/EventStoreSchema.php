<?php

declare(strict_types=1);

namespace App\Tests\Chapter06;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * SQLite v paměti se stejnými tabulkami, jaké zakládá migrace
 * Version20260924130600.
 */
final class EventStoreSchema
{
    public static function connection(): Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $connection->executeStatement('CREATE TABLE ch06_event_store (
            id             INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            event_id       CHAR(36)     NOT NULL,
            aggregate_id   CHAR(36)     NOT NULL,
            aggregate_type VARCHAR(255) NOT NULL,
            event_type     VARCHAR(255) NOT NULL,
            payload        CLOB         NOT NULL,
            metadata       CLOB         NOT NULL DEFAULT \'{}\',
            schema_version SMALLINT     NOT NULL DEFAULT 1,
            version        INTEGER      NOT NULL,
            occurred_on    VARCHAR(26)  NOT NULL
        )');
        $connection->executeStatement('CREATE UNIQUE INDEX uq_ch06_event_id ON ch06_event_store (event_id)');
        $connection->executeStatement('CREATE UNIQUE INDEX uq_ch06_aggregate_version ON ch06_event_store (aggregate_id, version)');

        $connection->executeStatement('CREATE TABLE ch06_order_summary (
            order_id     VARCHAR(36)  NOT NULL PRIMARY KEY,
            customer_id  VARCHAR(36)  NOT NULL,
            status       VARCHAR(20)  NOT NULL,
            item_count   INTEGER      NOT NULL DEFAULT 0,
            total_amount BIGINT       NOT NULL DEFAULT 0,
            placed_at    DATETIME     NOT NULL,
            shipped_at   DATETIME     DEFAULT NULL,
            tracking_no  VARCHAR(100) DEFAULT NULL
        )');

        return $connection;
    }
}
