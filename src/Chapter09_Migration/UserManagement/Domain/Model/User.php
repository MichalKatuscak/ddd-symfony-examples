<?php

declare(strict_types=1);

// PO: Doménová entita s vlastními invarianty
namespace App\Chapter09_Migration\UserManagement\Domain\Model;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use App\Chapter09_Migration\UserManagement\Domain\Event\UserActivated;
use App\Chapter09_Migration\UserManagement\Domain\Event\UserRegistered;
use App\Chapter09_Migration\UserManagement\Domain\Exception\InvalidVerificationTokenException;
use App\Chapter09_Migration\UserManagement\Domain\Exception\UserAlreadyActivatedException;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\UserStatus;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\VerificationToken;
use App\Shared\Domain\AggregateRoot;

/**
 * Cílový stav migrace: kanonický User z kapitoly Implementace v Symfony
 * rozšířený o aktivaci. Vlastnosti createdAt a hashedPassword i getter
 * hashedPassword() zůstávají beze změny, přibývá stav a ověřovací token.
 *
 * Kniha entitu mapuje atributy Doctrine na tabulku `um_users` (po dobu
 * souběhu s legacy tabulkou `users`). Ukázka ji neukládá, atributy proto nemá.
 */
final class User extends AggregateRoot
{
    public readonly UserId $id;

    // Legacy tabulka sloupec `name` má a RegisterUser ho nese. Bez něj
    // by migrace jméno tiše zahodila.
    private UserName $name;

    private Email $email;

    private readonly HashedPassword $hashedPassword;

    private UserStatus $status;

    public readonly \DateTimeImmutable $createdAt;

    private ?VerificationToken $verificationToken;

    // Čas registrace a token přijímá konstruktor, protože `createdAt`
    // je readonly a podruhé se přiřadit nedá. Rekonstrukce z legacy dat
    // proto musí obojí předat rovnou sem.
    private function __construct(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $hashedPassword,
        ?\DateTimeImmutable $createdAt = null,
        ?VerificationToken $verificationToken = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->hashedPassword = $hashedPassword;
        $this->status = UserStatus::PendingVerification;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->verificationToken = $verificationToken ?? VerificationToken::generate();
    }

    // Named constructor vyjadřuje záměr lépe než new User()
    public static function register(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $hashedPassword,
    ): self {
        $user = new self($id, $name, $email, $hashedPassword);
        // Doménová událost – vedlejší efekt registrace je nyní explicitní.
        // Nahrává ji named constructor, ne __construct: rekonstituce událost nevyvolá.
        $user->record(new UserRegistered($id, $email, $user->createdAt));

        return $user;
    }

    // Rekonstituce z persistence nebo z ACL nad legacy tabulkou.
    // Nastavuje stav tak, jak byl uložen, a nezaznamenává žádnou událost.
    public static function reconstitute(
        UserId $id,
        UserName $name,
        Email $email,
        HashedPassword $hashedPassword,
        UserStatus $status,
        \DateTimeImmutable $createdAt,
        ?VerificationToken $verificationToken,
    ): self {
        // Bez předání původních hodnot dostane každý migrovaný uživatel
        // dnešní datum registrace a nový token – a aktivační odkaz,
        // který mu systém poslal, přestane platit.
        $user = new self($id, $name, $email, $hashedPassword, $createdAt, $verificationToken);
        $user->status = $status;

        return $user;
    }

    public function activate(VerificationToken $token): void
    {
        if ($this->status !== UserStatus::PendingVerification) {
            throw UserAlreadyActivatedException::forUser($this->id);
        }
        // Token musí odpovídat tomu, který entita vydala. Bez tohoto
        // porovnání aktivuje libovolný platný token libovolný účet.
        if (!$this->verificationToken->equals($token)) {
            throw InvalidVerificationTokenException::forUser($this->id);
        }
        $this->status = UserStatus::Active;
        $this->verificationToken = null;
        $this->record(new UserActivated($this->id, new \DateTimeImmutable()));
    }

    public function name(): UserName { return $this->name; }
    public function email(): Email { return $this->email; }
    public function hashedPassword(): HashedPassword { return $this->hashedPassword; }
    public function status(): UserStatus { return $this->status; }

    // Token potřebuje odesílatel aktivačního e-mailu i test; agregát
    // ho vydává jen ke čtení, nastavit ho zvenčí nejde.
    public function verificationToken(): ?VerificationToken { return $this->verificationToken; }
}
