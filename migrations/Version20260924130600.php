<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Kapitola 13 (ukázka Chapter06_EventSourcing): Event Store a projekce podle knihy.
 *
 * Nahrazuje ORM tabulky ch06_event_store (event_class, bez verze streamu)
 * a ch06_order_projection. Nový event store nese sloupce ze sekce 13.05
 * včetně unikátního indexu (aggregate_id, version) pro optimistic locking.
 * DDL je pro SQLite; kniha ho píše pro MySQL 8. Komentáře stojí zde, ne
 * uvnitř CREATE TABLE – SQL komentáře v DDL rozhodí introspekci SQLite.
 */
final class Version20260924130600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Chapter06: event store s verzí streamu a projekce ch06_order_summary';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ch06_order_projection');
        $this->addSql('DROP TABLE IF EXISTS ch06_event_store');

        $this->addSql('CREATE TABLE ch06_event_store (
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
        $this->addSql('CREATE UNIQUE INDEX uq_ch06_event_id ON ch06_event_store (event_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_ch06_aggregate_version ON ch06_event_store (aggregate_id, version)');
        $this->addSql('CREATE INDEX idx_ch06_aggregate_type ON ch06_event_store (aggregate_type)');
        $this->addSql('CREATE INDEX idx_ch06_event_type ON ch06_event_store (event_type)');
        $this->addSql('CREATE INDEX idx_ch06_occurred_on ON ch06_event_store (occurred_on)');

        $this->addSql('CREATE TABLE ch06_order_summary (
            order_id     VARCHAR(36)  NOT NULL PRIMARY KEY,
            customer_id  VARCHAR(36)  NOT NULL,
            status       VARCHAR(20)  NOT NULL,
            item_count   INTEGER      NOT NULL DEFAULT 0,
            total_amount BIGINT       NOT NULL DEFAULT 0,
            placed_at    DATETIME     NOT NULL,
            shipped_at   DATETIME     DEFAULT NULL,
            tracking_no  VARCHAR(100) DEFAULT NULL
        )');
        $this->addSql('CREATE INDEX idx_ch06_summary_customer ON ch06_order_summary (customer_id, placed_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ch06_order_summary');
        $this->addSql('DROP TABLE ch06_event_store');

        $this->addSql('CREATE TABLE ch06_event_store (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, aggregate_id VARCHAR(36) NOT NULL, event_class VARCHAR(255) NOT NULL, payload CLOB NOT NULL, occurred_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE ch06_order_projection (
            order_id VARCHAR(36) NOT NULL,
            customer_id VARCHAR(255) NOT NULL,
            total_amount INTEGER NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (order_id)
        )');
    }
}
