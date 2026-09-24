<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\UserManagement\Domain;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class ValueObjectsTest extends TestCase
{
    public function test_email_constructor_validates_but_does_not_normalize(): void
    {
        // Normalizace patří fromUserInput(); konstruktor mezery neořízne.
        $this->expectException(\InvalidArgumentException::class);

        new Email(' jan@example.com ');
    }

    public function test_email_from_user_input_trims_and_lowercases(): void
    {
        $email = Email::fromUserInput('  Jan@Example.COM ');

        self::assertSame('jan@example.com', $email->value);
        self::assertTrue($email->equals(new Email('jan@example.com')));
    }

    public function test_user_name_is_trimmed_and_bounded(): void
    {
        self::assertSame('Jan', (new UserName('  Jan  '))->value);

        $this->expectException(\InvalidArgumentException::class);
        new UserName(' a ');
    }

    public function test_hashed_password_rejects_short_password(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        HashedPassword::fromPlainText('kratke');
    }

    public function test_hashed_password_never_keeps_plain_text(): void
    {
        $password = HashedPassword::fromPlainText('securepassword123');

        self::assertNotSame('securepassword123', $password->value);
        self::assertTrue($password->matches('securepassword123'));
        // Rekonstituce z databáze hash znovu nehashuje.
        self::assertSame($password->value, HashedPassword::fromHash($password->value)->value);
    }

    public function test_user_id_is_uuid_v7(): void
    {
        $id = UserId::generate();

        self::assertInstanceOf(UuidV7::class, Uuid::fromString($id->value));
        self::assertTrue($id->equals(UserId::fromString($id->value)));
        self::assertSame($id->value, (string) $id);
    }

    public function test_user_id_rejects_non_uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UserId('student-42');
    }
}
