<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Domain\ValueObject;

enum UserStatus: string
{
    case PendingVerification = 'pending_verification';
    case Active = 'active';
    // Inactive si zvolí uživatel sám, Blocked je zásah provozovatele.
    // Na Blocked translator mapuje legacy hodnoty „banned“ i „deleted“.
    case Inactive = 'inactive';
    case Blocked = 'blocked';

    public function isPendingVerification(): bool
    {
        return $this === self::PendingVerification;
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
