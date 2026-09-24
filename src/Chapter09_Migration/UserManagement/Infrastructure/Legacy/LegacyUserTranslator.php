<?php

declare(strict_types=1);

// ACL žije v infrastruktuře nového Bounded Contextu.
// Doménová vrstva o legacy tabulce ani o této třídě neví.
namespace App\Chapter09_Migration\UserManagement\Infrastructure\Legacy;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use App\Chapter09_Migration\UserManagement\Domain\Model\User;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\UserStatus;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\VerificationToken;
use App\Chapter09_Migration\UserManagement\Infrastructure\Legacy\Exception\UnmappableLegacyStatusException;

final class LegacyUserTranslator
{
    /**
     * @param array<string, mixed> $row řádek z legacy tabulky `users`
     */
    public function toDomain(array $row): User
    {
        // Legacy sloupec zná hodnoty, které doména nemá.
        // Mapování je vyjmenované: neznámý stav je chyba, ne tichý default.
        $status = match ($row['status']) {
            'pending_verification' => UserStatus::PendingVerification,
            'active'               => UserStatus::Active,
            'banned', 'deleted'    => UserStatus::Blocked,
            default => throw new UnmappableLegacyStatusException((string) $row['status']),
        };

        // Rekonstituce, ne registrace: žádná doménová událost nevzniká.
        // Čas registrace a token se přebírají z legacy řádku; kdyby si je
        // agregát přidělil sám, migrovaní uživatelé by přišli o historii
        // i o platný aktivační odkaz.
        return User::reconstitute(
            UserId::fromString((string) $row['uuid']),
            new UserName((string) $row['name']),
            new Email((string) $row['email']),
            HashedPassword::fromHash((string) $row['password']),
            $status,
            new \DateTimeImmutable((string) $row['created_at']),
            $row['verification_token'] !== null
                ? VerificationToken::fromString((string) $row['verification_token'])
                : null,
        );
    }
}
