<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\UserManagement\Registration;

use App\Chapter04_Implementation\UserManagement\Domain\Event\UserRegistered;
use App\Chapter04_Implementation\UserManagement\Domain\Exception\DuplicateEmailException;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Infrastructure\Repository\DoctrineUserRepository;
use App\Chapter04_Implementation\UserManagement\Registration\Command\RegisterUser;
use App\Chapter04_Implementation\UserManagement\Registration\Command\RegisterUserHandler;
use App\Tests\Chapter04\UserManagement\SqliteUserManagement;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class RegisterUserHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private DoctrineUserRepository $users;

    /** @var \ArrayObject<int, array{event: object, rowsAtDispatch: int}> */
    private \ArrayObject $dispatched;

    private MessageBusInterface $eventBus;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->em = SqliteUserManagement::entityManager();
        $this->users = new DoctrineUserRepository($this->em);
        $this->dispatched = new \ArrayObject();

        // Event bus si u každé zprávy poznamená, kolik řádků už leží
        // v tabulce. Tak test pozná, že dispatch přišel až po flushi.
        $this->eventBus = new class ($this->em->getConnection(), $this->dispatched) implements MessageBusInterface {
            /** @param \ArrayObject<int, array{event: object, rowsAtDispatch: int}> $log */
            public function __construct(
                private readonly Connection $connection,
                private readonly \ArrayObject $log,
            ) {}

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->log[] = [
                    'event' => $message,
                    'rowsAtDispatch' => (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ch04_users'),
                ];

                return new Envelope($message, $stamps);
            }
        };

        $this->handler = new RegisterUserHandler($this->users, $this->em, $this->eventBus);
    }

    public function test_registers_new_user(): void
    {
        ($this->handler)(new RegisterUser(
            name: 'Jan Novák',
            email: 'jan@example.com',
            password: 'securepassword123',
        ));
        $this->em->clear();

        $user = $this->users->findByEmail(Email::fromUserInput('jan@example.com'));

        self::assertNotNull($user);
        self::assertSame('Jan Novák', $user->name()->value);
        self::assertTrue($user->hashedPassword()->matches('securepassword123'));
    }

    public function test_dispatches_user_registered_after_flush(): void
    {
        ($this->handler)(new RegisterUser(
            name: 'Jan Novák',
            email: 'Jan@Example.com',
            password: 'securepassword123',
        ));

        self::assertCount(1, $this->dispatched);
        $event = $this->dispatched[0]['event'];
        self::assertInstanceOf(UserRegistered::class, $event);
        self::assertSame('jan@example.com', $event->email);
        self::assertSame(1, $this->dispatched[0]['rowsAtDispatch']);
    }

    public function test_rejects_duplicate_email_through_unique_index(): void
    {
        ($this->handler)(new RegisterUser(
            name: 'Jan Novák',
            email: 'jan@example.com',
            password: 'securepassword123',
        ));

        // Druhý handler dostane čerstvý EntityManager, jako by šlo o souběžný
        // požadavek: identity map prvního o zápisu nic neví.
        $this->handler = new RegisterUserHandler(
            new DoctrineUserRepository($em = $this->secondEntityManagerOnSameConnection()),
            $em,
            $this->eventBus,
        );

        try {
            // Velikost písmen srovná Email::fromUserInput(), takže kolizi
            // zachytí unique index, ne porovnání řetězců.
            ($this->handler)(new RegisterUser(
                name: 'Jan Jiný',
                email: 'JAN@example.com',
                password: 'jineheslo456789',
            ));
            self::fail('Druhá registrace téže adresy měla selhat.');
        } catch (DuplicateEmailException $e) {
            self::assertInstanceOf(UniqueConstraintViolationException::class, $e->getPrevious());
        }

        self::assertCount(1, $this->dispatched, 'Neúspěšná registrace nesmí vydat událost.');
    }

    private function secondEntityManagerOnSameConnection(): EntityManagerInterface
    {
        return new EntityManager($this->em->getConnection(), $this->em->getConfiguration());
    }
}
