<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Infrastructure\Legacy\Exception;

// Chyba hranice, ne domény: legacy řádek nese stav, který model nezná.
// Proto dědí z \RuntimeException a žije v infrastruktuře vedle translatoru.
final class UnmappableLegacyStatusException extends \RuntimeException
{
    public function __construct(string $legacyStatus)
    {
        parent::__construct(sprintf('Legacy status "%s" has no domain counterpart.', $legacyStatus));
    }
}
