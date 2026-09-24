<?php

declare(strict_types=1);

namespace App\Tests\Chapter09\UserManagement\Domain\Model;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use App\Chapter09_Migration\UserManagement\Domain\Event\UserActivated;
use App\Chapter09_Migration\UserManagement\Domain\Event\UserRegistered;
use App\Chapter09_Migration\UserManagement\Domain\Exception\InvalidVerificationTokenException;
use App\Chapter09_Migration\UserManagement\Domain\Exception\UserAlreadyActivatedException;
use App\Chapter09_Migration\UserManagement\Domain\Model\User;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\UserStatus;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\VerificationToken;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_newly_registered_user_is_pending_verification(): void
    {
        $user = User::register(
            UserId::generate(),
            new UserName('Jan Novák'),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlainText('SecurePass123'),
        );

        self::assertSame(UserStatus::PendingVerification, $user->status());
    }

    public function test_registration_emits_user_registered_event(): void
    {
        $user = User::register(
            UserId::generate(),
            new UserName('Jan Novák'),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlainText('SecurePass123'),
        );

        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
    }

    public function test_cannot_activate_already_active_user(): void
    {
        $user = User::register(
            UserId::generate(),
            new UserName('Jan Novák'),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlainText('SecurePass123'),
        );
        // Token přiděluje agregát při registraci; podstrčený řetězec
        // by neprošel kontrolou a test by spadl už na prvním activate().
        $token = $user->verificationToken();
        $user->activate($token);

        $this->expectException(UserAlreadyActivatedException::class);
        $user->activate($token); // druhá aktivace musí selhat
    }

    public function test_activation_records_user_activated_and_clears_token(): void
    {
        $user = $this->registeredUser();
        $user->releaseEvents(); // registrace nahrála UserRegistered

        $user->activate($user->verificationToken());

        self::assertSame(UserStatus::Active, $user->status());
        self::assertNull($user->verificationToken());
        $events = $user->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserActivated::class, $events[0]);
        self::assertTrue($user->id->equals($events[0]->userId));
    }

    public function test_foreign_token_does_not_activate_account(): void
    {
        $user = $this->registeredUser();

        $this->expectException(InvalidVerificationTokenException::class);
        $user->activate(VerificationToken::generate());
    }

    public function test_reconstitution_keeps_stored_values_and_records_nothing(): void
    {
        $id = UserId::generate();
        $createdAt = new \DateTimeImmutable('2019-03-01 08:00:00');
        $token = VerificationToken::fromString('legacy-token');

        $user = User::reconstitute(
            $id,
            new UserName('Jan Novák'),
            new Email('jan@firma.cz'),
            HashedPassword::fromHash('$2y$10$legacyhash'),
            UserStatus::PendingVerification,
            $createdAt,
            $token,
        );

        self::assertSame($createdAt, $user->createdAt);
        self::assertTrue($token->equals($user->verificationToken()));
        self::assertSame([], $user->releaseEvents());
    }

    private function registeredUser(): User
    {
        return User::register(
            UserId::generate(),
            new UserName('Jan Novák'),
            new Email('jan@firma.cz'),
            HashedPassword::fromPlainText('SecurePass123'),
        );
    }
}
