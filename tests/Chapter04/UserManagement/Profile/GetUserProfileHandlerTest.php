<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\UserManagement\Profile;

use App\Chapter04_Implementation\UserManagement\Domain\Model\User;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use App\Chapter04_Implementation\UserManagement\Infrastructure\Repository\DoctrineUserRepository;
use App\Chapter04_Implementation\UserManagement\Profile\Query\GetUserProfile;
use App\Chapter04_Implementation\UserManagement\Profile\Query\GetUserProfileHandler;
use App\Chapter04_Implementation\UserManagement\Profile\Query\UserProfile;
use App\Tests\Chapter04\UserManagement\SqliteUserManagement;
use PHPUnit\Framework\TestCase;

final class GetUserProfileHandlerTest extends TestCase
{
    public function test_returns_dto_for_stored_user(): void
    {
        $em = SqliteUserManagement::entityManager();
        $users = new DoctrineUserRepository($em);
        $user = User::register(
            UserId::generate(),
            new UserName('Jan Novák'),
            Email::fromUserInput('jan@example.com'),
            HashedPassword::fromPlainText('securepassword123'),
        );
        $users->save($user);
        $em->flush();
        $em->clear();

        $profile = (new GetUserProfileHandler($users))(new GetUserProfile($user->id->value));

        self::assertInstanceOf(UserProfile::class, $profile);
        self::assertSame($user->id->value, $profile->id);
        self::assertSame('Jan Novák', $profile->name);
        self::assertSame('jan@example.com', $profile->email);
    }

    public function test_returns_null_for_unknown_user(): void
    {
        $users = new DoctrineUserRepository(SqliteUserManagement::entityManager());

        self::assertNull((new GetUserProfileHandler($users))(new GetUserProfile(UserId::generate()->value)));
    }
}
