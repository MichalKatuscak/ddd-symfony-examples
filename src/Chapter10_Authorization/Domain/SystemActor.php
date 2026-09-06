<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Domain;

/**
 * Aktér pro procesy bez člověka: ságy, cron, batch. Bydlí na jednom místě –
 * kdyby ho sága a handler držely každý zvlášť, rozejdou se při první změně.
 */
final class SystemActor
{
    public const ID = '01920000-0000-7000-8000-000000000001';
}
