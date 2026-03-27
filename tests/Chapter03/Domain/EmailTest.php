<?php

declare(strict_types=1);
namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testValidEmail(): void
    {
        $email = new Email('jan@example.com');
        $this->assertSame('jan@example.com', (string) $email);
    }

    public function testInvalidEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }
}
