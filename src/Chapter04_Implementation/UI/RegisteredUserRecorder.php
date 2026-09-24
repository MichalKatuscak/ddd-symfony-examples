<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UI;

use App\Chapter04_Implementation\UserManagement\Domain\Event\UserRegistered;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Posluchač jen pro stránku ukázky: zapamatuje si poslední registraci,
 * aby kontroler mohl přesměrovat na profil. Kniha tu má posluchače
 * z kontextu Identity, který zakládá přihlašovací záznam.
 *
 * Stačí mu primitivy z události – do UserRepository nesahá.
 */
#[AsMessageHandler(bus: 'event.bus')]
final class RegisteredUserRecorder
{
    private ?UserRegistered $last = null;

    public function __invoke(UserRegistered $event): void
    {
        $this->last = $event;
    }

    public function last(): ?UserRegistered
    {
        return $this->last;
    }
}
