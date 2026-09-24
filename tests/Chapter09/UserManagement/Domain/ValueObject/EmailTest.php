<?php

declare(strict_types=1);

namespace App\Tests\Chapter09\UserManagement\Domain\ValueObject;

use App\Chapter09_Migration\UserManagement\Domain\Exception\ForbiddenEmailDomainException;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_invalid_address_is_rejected_by_constructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }

    public function test_user_input_is_normalized(): void
    {
        self::assertSame('jan@firma.cz', Email::fromUserInput('  Jan@Firma.CZ ')->value);
    }

    #[TestWith(['someone@mailinator.com'])]
    #[TestWith(['Someone@GuerrillaMail.com'])]
    public function test_user_input_from_forbidden_domain_is_rejected(string $input): void
    {
        $this->expectException(ForbiddenEmailDomainException::class);
        Email::fromUserInput($input);
    }

    public function test_constructor_accepts_forbidden_domain_for_rehydration(): void
    {
        // Legacy řádek s takovou adresou musí jít načíst – pravidlo platí
        // jen pro nový uživatelský vstup.
        self::assertSame('mailinator.com', (new Email('old@mailinator.com'))->domain());
    }
}
