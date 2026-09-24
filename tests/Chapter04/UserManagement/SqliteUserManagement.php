<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\UserManagement;

use App\Chapter04_Implementation\UserManagement\Infrastructure\Doctrine\Type\EmailType;
use App\Chapter04_Implementation\UserManagement\Infrastructure\Doctrine\Type\UserIdType;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use DoctrineMigrations\Version20260924100000;
use Psr\Log\NullLogger;

/**
 * Skutečný EntityManager nad SQLite v paměti. Unique constraint a flush
 * ověří jen reálná databáze – mock repozitáře žádný index nemá.
 *
 * Schéma zakládá tatáž migrace, kterou spouští `make install`, takže test
 * hlídá i ji.
 */
final class SqliteUserManagement
{
    public static function entityManager(): EntityManagerInterface
    {
        foreach (['ch04_email' => EmailType::class, 'ch04_user_id' => UserIdType::class] as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }

        $config = ORMSetup::createAttributeMetadataConfig(
            [dirname(__DIR__, 3) . '/src/Chapter04_Implementation/UserManagement/Domain'],
            isDevMode: true,
        );
        $config->enableNativeLazyObjects(true);
        // Stejná strategie jako v config/packages/doctrine.yaml.
        $config->setNamingStrategy(new UnderscoreNamingStrategy(\CASE_LOWER, true));

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);

        require_once dirname(__DIR__, 3) . '/migrations/Version20260924100000.php';
        $migration = new Version20260924100000($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement());
        }

        return new EntityManager($connection, $config);
    }
}
