<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\User\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_valid_email_is_kept_as_given(): void
    {
        // Konstruktor jen validuje, hodnotu nemění.
        $email = new Email('Jan@Example.com');

        self::assertSame('Jan@Example.com', $email->value);
    }

    public function test_invalid_email_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }

    public function test_padded_input_is_refused_by_constructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('  jan@example.com ');
    }

    public function test_from_user_input_normalizes(): void
    {
        $email = Email::fromUserInput('  Jan.Novak@Example.COM ');

        self::assertSame('jan.novak@example.com', $email->value);
        self::assertSame('jan.novak@example.com', (string) $email);
    }

    public function test_equality_by_value(): void
    {
        self::assertTrue(Email::fromUserInput('JAN@example.com')->equals(new Email('jan@example.com')));
        self::assertFalse((new Email('jan@example.com'))->equals(new Email('petr@example.com')));
    }
}
