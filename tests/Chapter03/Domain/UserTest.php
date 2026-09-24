<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\User\Email;
use App\Chapter03_BasicConcepts\Domain\User\User;
use App\Chapter03_BasicConcepts\Domain\User\UserId;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_identity_survives_attribute_change(): void
    {
        $user = new User(UserId::generate(), 'Jana', new Email('jana@example.com'));
        $id = $user->id;

        $user->changeName('Jana Nováková');
        $user->changeEmail(new Email('jana.novakova@example.com'));

        self::assertSame($id, $user->id);
        self::assertSame('jana.novakova@example.com', $user->email()->value);
    }

    public function test_equality_depends_on_identity_only(): void
    {
        $id = UserId::generate();
        $first = new User($id, 'Jana', new Email('jana@example.com'));
        $second = new User(UserId::fromString($id->value), 'Jana', new Email('jana@example.com'));
        $second->changeEmail(new Email('jana.novakova@example.com'));

        self::assertTrue($first->equals($second));
        // == srovnává všechny vlastnosti, === instance v paměti.
        self::assertFalse($first == $second);
        self::assertFalse($first === $second);
    }

    public function test_same_attributes_different_identity_are_two_entities(): void
    {
        $first = new User(UserId::generate(), 'Jana', new Email('jana@example.com'));
        $second = new User(UserId::generate(), 'Jana', new Email('jana@example.com'));

        self::assertFalse($first->equals($second));
    }
}
