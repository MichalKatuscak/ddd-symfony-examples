<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Registration\Command;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUser
{
    public function __construct(
        // Trim je tu schválně: UserName si vstup ořízne, takže bez něj
        // by „  a  “ prošlo délkovou kontrolou a spadlo až v hodnotovém
        // objektu jako 500. HTML formulář to maskuje, protože TextType
        // trimuje sám – JSON endpoint ne.
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(min: 2, max: 100, normalizer: 'trim')]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT)]
        public string $email,

        // Hranice musí sedět s HashedPassword::fromPlainText(). Volnější
        // pravidlo tady by pustilo heslo, které pak agregát odmítne.
        #[Assert\NotBlank]
        #[Assert\Length(min: 12)]
        public string $password,
    ) {}
}
