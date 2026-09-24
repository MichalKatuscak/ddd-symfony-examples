<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Application;

use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;

// Port: seznam zákazníků na blacklistu dodává Infrastructure vrstva
// (fraud detection, ručně vedený seznam). Specifikace dostává hotový list.
interface BlacklistRegistry
{
    /** @return list<CustomerId> */
    public function all(): array;
}
