<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\UserManagement\Registration\Command;

use App\Chapter04_Implementation\UserManagement\Domain\Exception\DuplicateEmailException;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Registration\Command\RegisterUser;
use App\Chapter04_Implementation\UserManagement\Registration\Command\RegisterUserHandler;
use App\Tests\Chapter08\UserManagement\Infrastructure\Repository\InMemoryUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
        // Handler volá flush() kvůli překladu unique violation. Fake repozitář
        // ho neřeší, takže EntityManager stačí jako stub, který nedělá nic.
        //
        // Pozor na hranici toho testu: fake vyhazuje DuplicateEmailException
        // rovnou ze save(), zatímco produkce ji dostane až z flush() jako
        // UniqueConstraintViolationException a handler ji teprve překládá.
        // Test tedy ověřuje reakci handleru na výjimku, ne ten překlad.
        //
        // Event bus je také stub. dispatch() má návratový typ Envelope a ta je
        // final, takže ji PHPUnit neumí podvrhnout automaticky – bez willReturn()
        // by stub vrátil null a volání spadlo na TypeError.
        $eventBus = $this->createStub(MessageBusInterface::class);
        $eventBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $this->handler = new RegisterUserHandler(
            $this->userRepository,
            $this->createStub(EntityManagerInterface::class),
            $eventBus,
        );
    }

    public function testRegistersNewUser(): void
    {
        $command = new RegisterUser(
            name: 'Jan Novák',
            email: 'jan@example.com',
            password: 'SilneHeslo123!'
        );

        ($this->handler)($command);

        $this->assertSame(1, $this->userRepository->count());

        $user = $this->userRepository->findByEmail(new Email('jan@example.com'));
        $this->assertNotNull($user);
        $this->assertSame('jan@example.com', $user->email()->value);
        $this->assertSame('Jan Novák', (string) $user->name());
    }

    public function testNormalizesEmailBeforeRegistration(): void
    {
        $command = new RegisterUser(name: 'Jan Novák', email: 'Jan@Example.com', password: 'SilneHeslo123');

        ($this->handler)($command);

        // Handler prošel vstup přes Email::fromUserInput(), uložený e-mail je malými písmeny
        $this->assertNotNull($this->userRepository->findByEmail(new Email('jan@example.com')));
    }

    public function testThrowsExceptionWhenEmailAlreadyTaken(): void
    {
        $command = new RegisterUser(name: 'Jan Novák', email: 'jan@example.com', password: 'SilneHeslo123');
        ($this->handler)($command); // první registrace

        $this->expectException(DuplicateEmailException::class);

        ($this->handler)($command); // duplicitní registrace
    }

    public function testDoesNotPersistUserWhenEmailAlreadyTaken(): void
    {
        $command = new RegisterUser(name: 'Jan Novák', email: 'jan@example.com', password: 'SilneHeslo123');
        ($this->handler)($command);

        try {
            ($this->handler)($command);
        } catch (DuplicateEmailException) {
            // očekáváno
        }

        $this->assertSame(1, $this->userRepository->count());
    }
}
