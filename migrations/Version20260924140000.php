<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Úklid po přestavbě ukázek Chapter04_Implementation a Chapter05_CQRS.
 *
 * Tabulky ch04_orders a ch05_orders patřily původním ukázkám s XML mappingem
 * objednávky. Chapter04 dnes ukládá uživatele (ch04_users), Chapter05 čte
 * z projekce ch05_order_dashboard a write model drží v paměti.
 */
final class Version20260924140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Chapter04/05: zrušení tabulek ch04_orders a ch05_orders po přestavbě ukázek';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ch04_orders');
        $this->addSql('DROP TABLE IF EXISTS ch05_orders');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ch04_orders (id VARCHAR(36) NOT NULL, customer_id VARCHAR(255) NOT NULL, total_amount INTEGER NOT NULL, status VARCHAR(20) NOT NULL, items CLOB NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE ch05_orders (id VARCHAR(36) NOT NULL, customer_id VARCHAR(255) NOT NULL, total_amount INTEGER NOT NULL, items CLOB NOT NULL, PRIMARY KEY (id))');
    }
}
