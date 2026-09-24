<?php

declare(strict_types=1);

namespace App\Tests\Chapter09\UserManagement\Registration\Command;

use App\Chapter09_Migration\UserManagement\Domain\Exception\DuplicateEmailException;
use App\Chapter09_Migration\UserManagement\Domain\Exception\ForbiddenEmailDomainException;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\UserStatus;
use App\Chapter09_Migration\UserManagement\Registration\Command\RegisterUser;
use App\Chapter09_Migration\UserManagement\Registration\Command\RegisterUserHandler;
use App\Tests\Chapter09\UserManagement\Infrastructure\Repository\InMemoryUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $users;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        // Flush s překladem unique violation ověří až integrační test proti
        // databázi; fake hází DuplicateEmailException rovnou ze save().
        $this->handler = new RegisterUserHandler(
            $this->users,
            $this->createStub(EntityManagerInterface::class),
        );
    }

    public function test_registers_pending_user_with_normalized_email(): void
    {
        ($this->handler)(new RegisterUser(name: 'Jan Novák', email: ' Jan@Firma.cz', password: 'SecurePass123'));

        $user = $this->users->findByEmail(new Email('jan@firma.cz'));
        self::assertNotNull($user);
        self::assertSame(UserStatus::PendingVerification, $user->status());
        self::assertSame('Jan Novák', (string) $user->name());
    }

    public function test_rejects_forbidden_domain(): void
    {
        $this->expectException(ForbiddenEmailDomainException::class);

        ($this->handler)(new RegisterUser(name: 'Jan Novák', email: 'jan@mailinator.com', password: 'SecurePass123'));
    }

    public function test_rejects_duplicate_email(): void
    {
        $command = new RegisterUser(name: 'Jan Novák', email: 'jan@firma.cz', password: 'SecurePass123');
        ($this->handler)($command);

        $this->expectException(DuplicateEmailException::class);
        ($this->handler)($command);
    }
}
