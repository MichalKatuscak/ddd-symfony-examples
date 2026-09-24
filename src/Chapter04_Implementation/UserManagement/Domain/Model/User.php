<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Domain\Model;

use App\Chapter04_Implementation\UserManagement\Domain\Event\UserRegistered;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use App\Shared\Domain\AggregateRoot;
use Doctrine\ORM\Mapping as ORM;

/**
 * Kořen agregátu User z kapitoly Implementace v Symfony (10.03).
 *
 * Mapovací atributy leží přímo na doménové třídě, stejně jako v knize.
 * Tabulka a jména typů nesou prefix ch04_, protože všechny ukázky
 * sdílejí jednu databázi.
 */
#[ORM\Entity]
#[ORM\Table(name: 'ch04_users')]
final class User extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'ch04_user_id')]
    public readonly UserId $id;

    #[ORM\Embedded(class: UserName::class)]
    private UserName $name;

    #[ORM\Column(type: 'ch04_email', unique: true)]
    private Email $email;

    #[ORM\Embedded(class: HashedPassword::class)]
    private readonly HashedPassword $hashedPassword;

    #[ORM\Column(type: 'datetime_immutable')]
    public readonly \DateTimeImmutable $createdAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    private function __construct(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $hashedPassword,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->hashedPassword = $hashedPassword;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function register(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $hashedPassword,
    ): self {
        $user = new self($id, $name, $email, $hashedPassword);
        // Událost nahrává továrna, ne konstruktor: konstruktorem prochází
        // i rekonstituce a ta žádnou událost vyvolat nesmí.
        $user->record(new UserRegistered($id, $email, $user->createdAt));

        return $user;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    // Hash čte security vrstva při zakládání přihlašovacího záznamu.
    // Heslo v čitelné podobě agregát nezná a znát nemá.
    public function hashedPassword(): HashedPassword
    {
        return $this->hashedPassword;
    }

    public function rename(UserName $newName): void
    {
        if ($this->name->equals($newName)) {
            return;
        }

        $this->name = $newName;
    }

    public function changeEmail(Email $newEmail): void
    {
        if ($this->email->equals($newEmail)) {
            return;
        }

        $this->email = $newEmail;
    }
}
