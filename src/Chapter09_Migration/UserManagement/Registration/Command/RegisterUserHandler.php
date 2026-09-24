<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Registration\Command;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use App\Chapter09_Migration\UserManagement\Domain\Exception\DuplicateEmailException;
use App\Chapter09_Migration\UserManagement\Domain\Model\User;
use App\Chapter09_Migration\UserManagement\Domain\Repository\UserRepository;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Handler zapouzdřuje aplikační logiku jednoho use case.
 *
 * V knize nese #[AsMessageHandler(bus: 'command.bus')]. Ukázka atribut
 * vynechává: sběrnice se v tomto projektu jmenují messenger.bus.*
 * a Doctrine repozitář uživatelů tu není, takže handler volá jen test.
 */
final class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(RegisterUser $command): void
    {
        $email = Email::fromUserInput($command->email);
        $password = HashedPassword::fromPlainText($command->password);

        $user = User::register(
            UserId::generate(),
            new UserName($command->name),
            $email,
            $password,
        );

        $this->users->save($user);

        // Doménové události výřez vynechává. Handler je po save() vyzvedne
        // přes releaseEvents() a před flushem zapíše do outboxu, takže
        // odejdou ve stejné transakci – viz Recept 7 a kapitolu Outbox Pattern.

        // Flush patří handleru kvůli unique constraintu na e-mailu: jeho
        // porušení se tak přeloží na doménovou výjimku ještě zde. Commit
        // pak řídí doctrine_transaction middleware command busu.
        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw DuplicateEmailException::with($email, $e);
        }
    }
}
