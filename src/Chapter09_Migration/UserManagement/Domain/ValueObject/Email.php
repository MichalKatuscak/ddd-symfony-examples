<?php

declare(strict_types=1);

// PO: Email jako Value Object – validace je na jednom místě
namespace App\Chapter09_Migration\UserManagement\Domain\ValueObject;

use App\Chapter09_Migration\UserManagement\Domain\Exception\ForbiddenEmailDomainException;

final readonly class Email
{
    public function __construct(public string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid e-mail address.', $value)
            );
        }

        // Zakázané domény patří do továrny pro uživatelský vstup, ne do
        // konstruktoru. Konstruktorem prochází i rehydratace z databáze,
        // takže by toto pravidlo znemožnilo načíst legacy řádky, které
        // takovou adresu už obsahují.
    }

    // Normalizace vstupu (lowercase, trim) i zakázané domény patří sem,
    // ne do konstruktoru.
    public static function fromUserInput(string $input): self
    {
        $email = new self(mb_strtolower(trim($input)));

        if (in_array($email->domain(), ['mailinator.com', 'guerrillamail.com'], true)) {
            throw ForbiddenEmailDomainException::forDomain($email->domain());
        }

        return $email;
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
