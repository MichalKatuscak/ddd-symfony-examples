<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Kapitola CQRS: denormalizovaný read model (ukázka Chapter05_CQRS).
 *
 * Kniha píše DDL pro PostgreSQL (UUID, TIMESTAMP(6)). Ukázky běží nad
 * SQLite, která čas drží jako text: formát Y-m-d H:i:s.u z projektoru
 * zachová mikrosekundy i lexikální pořadí, takže podmínka
 * `updated_at < :updatedAt` porovnává správně.
 */
final class Version20260924100100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Kapitola 5: read model ch05_order_dashboard';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ch05_order_dashboard (
            order_id     CHAR(36)     NOT NULL,
            customer_id  CHAR(36)     NOT NULL,
            total_amount INTEGER      NOT NULL,
            status       VARCHAR(32)  NOT NULL,
            shipment_id  CHAR(36)     DEFAULT NULL,
            placed_at    DATETIME     NOT NULL,
            updated_at   DATETIME     NOT NULL,
            -- ON CONFLICT (order_id) v projektoru se opírá právě o tento klíč.
            PRIMARY KEY (order_id)
        )');

        // Dashboard se řadí podle data a filtruje podle stavu.
        $this->addSql('CREATE INDEX idx_ch05_dashboard_status_placed
            ON ch05_order_dashboard (status, placed_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ch05_order_dashboard');
    }
}
