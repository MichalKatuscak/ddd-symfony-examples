<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ch06_order_projection table for read model';
    }

    public function up(Schema $schema): void
    {
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

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ch06_order_projection');
    }
}
