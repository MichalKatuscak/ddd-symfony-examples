<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\UserManagement\Domain\Model;

use App\Chapter04_Implementation\UserManagement\Domain\Event\UserRegistered;
use App\Chapter04_Implementation\UserManagement\Domain\Model\User;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private UserId $userId;
    private Email $email;

    protected function setUp(): void
    {
        $this->userId = UserId::generate();
        $this->email  = new Email('jan@example.com');
    }

    public function testRegistrationRecordsExactlyOneUserRegistered(): void
    {
        $user = User::register($this->userId, new UserName('Jan Novák'), $this->email, HashedPassword::fromPlainText('SilneHeslo123'));

        $events = $user->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserRegistered::class, $events[0]);
        $this->assertSame($this->userId->value, $events[0]->userId); // událost nese primitivy
        $this->assertSame('jan@example.com', $events[0]->email);
    }

    public function testRegistrationExposesGivenValues(): void
    {
        $user = User::register($this->userId, new UserName('Jan Novák'), $this->email, HashedPassword::fromPlainText('SilneHeslo123'));

        $this->assertTrue($this->userId->equals($user->id));
        $this->assertTrue($this->email->equals($user->email()));
        $this->assertSame('Jan Novák', (string) $user->name());
    }

    public function testRenamesUser(): void
    {
        $user = User::register($this->userId, new UserName('Jan Novák'), $this->email, HashedPassword::fromPlainText('SilneHeslo123'));

        $user->rename(new UserName('Jan Nový'));

        $this->assertSame('Jan Nový', (string) $user->name());
    }

    public function testRenameWithSameNameChangesNothing(): void
    {
        $user = User::register($this->userId, new UserName('Jan Novák'), $this->email, HashedPassword::fromPlainText('SilneHeslo123'));
        $user->releaseEvents(); // vyprázdní buffer – registrace nahrála UserRegistered

        $user->rename(new UserName('Jan Novák'));

        // Idempotence: stejné jméno agregát ignoruje a nic nenahrává
        $this->assertSame('Jan Novák', (string) $user->name());
        $this->assertCount(0, $user->releaseEvents());
    }

    public function testChangesEmailAddress(): void
    {
        $user     = User::register($this->userId, new UserName('Jan Novák'), $this->email, HashedPassword::fromPlainText('SilneHeslo123'));
        $newEmail = new Email('novy@example.com');

        $user->changeEmail($newEmail);

        $this->assertTrue($newEmail->equals($user->email()));
    }

    public function testEmailRemainsUnchangedWhenSameValueProvided(): void
    {
        $user = User::register($this->userId, new UserName('Jan Novák'), $this->email, HashedPassword::fromPlainText('SilneHeslo123'));
        $user->releaseEvents(); // vyprázdní buffer – registrace nahrála UserRegistered

        $user->changeEmail(new Email('jan@example.com'));

        // Stejný e-mail – agregát nic nezměnil a nic nenahrál
        $this->assertTrue($this->email->equals($user->email()));
        $this->assertCount(0, $user->releaseEvents());
    }
}
