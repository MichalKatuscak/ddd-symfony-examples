<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\UserManagement\Domain\ValueObject;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testCreatesValidEmail(): void
    {
        $email = new Email('jan.novak@example.com');

        $this->assertSame('jan.novak@example.com', $email->value);
    }

    public function testNormalizesToLowercase(): void
    {
        // Normalizaci dělá pojmenovaná factory, ne konstruktor
        $email = Email::fromUserInput('Jan.Novak@EXAMPLE.COM');

        $this->assertSame('jan.novak@example.com', $email->value);
    }

    #[DataProvider('invalidInputs')]
    public function testThrowsExceptionForInvalidInput(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email($input);
    }

    /**
     * Data provider musí být od PHPUnit 11 statický a veřejný.
     *
     * @return iterable<string, array{string}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'bez zavináče'    => ['not-an-email'];
        yield 'prázdný řetězec' => [''];
        yield 'chybí doména'    => ['jan@'];
        yield 'mezera uvnitř'   => ['jan novak@example.com'];
    }

    public function testEqualityBySameValue(): void
    {
        $email1 = new Email('jan@example.com');
        $email2 = new Email('jan@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function testInequalityForDifferentValues(): void
    {
        $email1 = new Email('jan@example.com');
        $email2 = new Email('petr@example.com');

        $this->assertFalse($email1->equals($email2));
    }

    public function testImmutabilityViaNewInstance(): void
    {
        $original = new Email('jan@example.com');
        // Hodnotové objekty jsou immutabilní - změna vyžaduje vytvoření nové instance
        $different = new Email('petr@example.com');

        $this->assertSame('jan@example.com', $original->value);
        $this->assertSame('petr@example.com', $different->value);
        $this->assertFalse($original->equals($different));
    }
}
