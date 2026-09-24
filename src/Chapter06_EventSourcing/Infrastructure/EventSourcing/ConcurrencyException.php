<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventSourcing;

/** Zápis narazil na cizí verzi streamu – agregát je nutné načíst znovu. */
final class ConcurrencyException extends \RuntimeException
{
}
