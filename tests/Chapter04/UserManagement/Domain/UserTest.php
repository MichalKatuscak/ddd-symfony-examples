<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\UserManagement\Domain;

use App\Chapter04_Implementation\UserManagement\Domain\Event\UserRegistered;
use App\Chapter04_Implementation\UserManagement\Domain\Model\User;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function register(): User
    {
        return User::register(
            UserId::generate(),
            new UserName('Jan Novák'),
            Email::fromUserInput('jan@example.com'),
            HashedPassword::fromPlainText('securepassword123'),
        );
    }

    public function test_register_records_user_registered_with_primitives(): void
    {
        $user = $this->register();

        $events = $user->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]);
        // Primitivy, ne hodnotové objekty – událost odebírá i jiný kontext.
        self::assertSame($user->id->value, $events[0]->userId);
        self::assertSame('jan@example.com', $events[0]->email);
        self::assertSame($user->createdAt, $events[0]->occurredAt);
    }

    public function test_release_events_empties_the_queue(): void
    {
        $user = $this->register();
        $user->releaseEvents();

        self::assertSame([], $user->releaseEvents());
    }

    public function test_rename_and_change_email_with_same_value_change_nothing(): void
    {
        $user = $this->register();

        $user->rename(new UserName('  Jan Novák  '));
        $user->changeEmail(new Email('jan@example.com'));

        self::assertSame('Jan Novák', $user->name()->value);
        self::assertSame('jan@example.com', $user->email()->value);
    }

    public function test_rename_and_change_email(): void
    {
        $user = $this->register();

        $user->rename(new UserName('Jana Nováková'));
        $user->changeEmail(Email::fromUserInput('jana@example.com'));

        self::assertSame('Jana Nováková', $user->name()->value);
        self::assertSame('jana@example.com', $user->email()->value);
    }
}
