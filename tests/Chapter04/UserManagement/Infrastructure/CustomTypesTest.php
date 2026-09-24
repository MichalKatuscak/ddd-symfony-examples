<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\UserManagement\Infrastructure;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Infrastructure\Doctrine\Type\EmailType;
use App\Chapter04_Implementation\UserManagement\Infrastructure\Doctrine\Type\UserIdType;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use PHPUnit\Framework\TestCase;

final class CustomTypesTest extends TestCase
{
    public function test_user_id_type_round_trip(): void
    {
        $type = new UserIdType();
        $platform = new SQLitePlatform();
        $id = UserId::generate();

        $stored = $type->convertToDatabaseValue($id, $platform);
        $loaded = $type->convertToPHPValue($stored, $platform);

        self::assertSame($id->value, $stored);
        self::assertInstanceOf(UserId::class, $loaded);
        self::assertTrue($id->equals($loaded));
    }

    public function test_user_id_type_fails_loudly_on_plain_string(): void
    {
        // Kdyby převod tiše vrátil null, dotaz by nic nenašel a nikdo by
        // se nedozvěděl proč.
        $this->expectException(InvalidType::class);

        (new UserIdType())->convertToDatabaseValue('01a07424-28ff-7c31-9d40-6f2a1c8e5b05', new SQLitePlatform());
    }

    public function test_email_type_round_trip(): void
    {
        $type = new EmailType();
        $platform = new SQLitePlatform();

        $loaded = $type->convertToPHPValue($type->convertToDatabaseValue(new Email('jan@example.com'), $platform), $platform);

        self::assertInstanceOf(Email::class, $loaded);
        self::assertSame('jan@example.com', $loaded->value);
    }
}
