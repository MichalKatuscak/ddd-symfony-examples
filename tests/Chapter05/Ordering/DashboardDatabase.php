<?php

declare(strict_types=1);

namespace App\Tests\Chapter05\Ordering;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\Version20260924100100;
use Psr\Log\NullLogger;

/**
 * SQLite v paměti se schématem z téže migrace, kterou spouští
 * `make install`. Projektor se testuje proti skutečnému SQL – upsert
 * s podmínkou na updated_at mock neověří.
 */
final class DashboardDatabase
{
    public static function connection(): Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        require_once dirname(__DIR__, 3) . '/migrations/Version20260924100100.php';
        $migration = new Version20260924100100($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement());
        }

        return $connection;
    }
}
