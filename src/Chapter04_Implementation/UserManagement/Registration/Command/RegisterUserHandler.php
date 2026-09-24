<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Registration\Command;

use App\Chapter04_Implementation\UserManagement\Domain\Exception\DuplicateEmailException;
use App\Chapter04_Implementation\UserManagement\Domain\Model\User;
use App\Chapter04_Implementation\UserManagement\Domain\Repository\UserRepository;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handler registrace z kapitoly Implementace v Symfony (10.13).
 *
 * Kniha ho váže na 'command.bus'; sdílená konfigurace ukázek tutéž
 * sběrnici jmenuje messenger.bus.command. Vazba na jednu sběrnici je
 * stejně explicitní jako v knize.
 */
#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(RegisterUser $command): void
    {
        $email = Email::fromUserInput($command->email);

        $user = User::register(
            UserId::generate(),
            new UserName($command->name),
            $email,
            HashedPassword::fromPlainText($command->password),
        );

        try {
            $this->userRepository->save($user);
            // Explicitní flush kvůli unique constraintu: porušení se ukáže
            // až při flushi a musí vybublat uvnitř tohoto try/catch.
            // S middlewarem doctrine_transaction (kniha) flush jen zapíše
            // SQL a commit proběhne po návratu handleru. Command bus ukázek
            // middleware nemá, takže zde flush rovnou i commitne.
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            // Spoléháme na DB unique constraint na sloupci `email`. Aplikační check
            // přes findByEmail() je vůči souběžným registracím nedostatečný (TOCTOU
            // race – dvě paralelní volání obě projdou check a obě uloží).
            throw DuplicateEmailException::with($email, $e);
        }

        // Bez tohoto kroku zůstane UserRegistered ležet v agregátu a nikdo
        // se o registraci nedozví. Posluchači ve stejném procesu smějí běžet
        // i uvnitř transakce; co opouští proces (e-mail, broker), patří
        // do Outboxu.
        foreach ($user->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
