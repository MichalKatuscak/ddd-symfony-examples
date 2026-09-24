<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\SharedKernel\Application\Command;

/**
 * Command, který lze kompenzovat – definuje svůj „undo“ příkaz.
 */
interface CompensatableCommand
{
    /**
     * Vrátí příkaz, který sémanticky vrátí efekt tohoto příkazu.
     */
    public function compensation(): object;
}
