<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Kapitola Implementace v Symfony: agregát User (ukázka Chapter04_Implementation).
 * Unikátní index na e-mailu je jediná ochrana proti souběžné registraci
 * téže adresy – handler ho překládá na DuplicateEmailException.
 */
final class Version20260924100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Kapitola 4: tabulka ch04_users s unikátním e-mailem';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ch04_users (
            id CHAR(36) NOT NULL,
            email VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            version INTEGER DEFAULT 1 NOT NULL,
            name_value VARCHAR(100) NOT NULL,
            hashed_password_value VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C879CE53E7927C74 ON ch04_users (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ch04_users');
    }
}
