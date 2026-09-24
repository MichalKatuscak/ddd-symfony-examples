<?php

declare(strict_types=1);

namespace App\Tests\Chapter09\CrudVersion;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use App\Chapter09_Migration\CrudVersion\User as CrudUser;
use App\Chapter09_Migration\UserManagement\Domain\Exception\UserAlreadyActivatedException;
use App\Chapter09_Migration\UserManagement\Domain\Model\User;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

/**
 * Tatáž pravidla před migrací a po ní. CRUD entita je nezná,
 * doménový model je vymáhá sám.
 */
final class CrudComparisonTest extends TestCase
{
    public function test_crud_entity_accepts_any_status(): void
    {
        $user = new CrudUser();
        $user->setStatus('pending_verification');
        // Žádný přechod, žádná kontrola – překlep projde až do databáze.
        $user->setStatus('aktivni');

        self::assertSame('aktivni', $user->getStatus());
    }

    public function test_crud_entity_accepts_any_email(): void
    {
        $user = new CrudUser();
        $user->setEmail('not-an-email');

        self::assertSame('not-an-email', $user->getEmail());
    }

    public function test_crud_entity_can_be_activated_twice(): void
    {
        $user = new CrudUser();
        $user->setStatus('active');
        $user->setStatus('active'); // o druhé aktivaci nikdo neví

        self::assertSame('active', $user->getStatus());
    }

    public function test_domain_model_rejects_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }

    public function test_domain_model_rejects_second_activation(): void
    {
        $user = User::register(
            UserId::generate(),
            new UserName('Jan Novák'),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlainText('SecurePass123'),
        );
        $token = $user->verificationToken();
        $user->activate($token);

        $this->expectException(UserAlreadyActivatedException::class);
        $user->activate($token);
    }
}
