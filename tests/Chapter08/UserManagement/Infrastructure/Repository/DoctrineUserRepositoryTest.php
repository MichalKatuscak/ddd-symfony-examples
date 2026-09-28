<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\UserManagement\Infrastructure\Repository;

use App\Chapter04_Implementation\UserManagement\Domain\Model\User;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use App\Chapter04_Implementation\UserManagement\Infrastructure\Repository\DoctrineUserRepository;
use App\Tests\Chapter04\UserManagement\SqliteUserManagement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integrační test DoctrineUserRepository podle 17.05.
 *
 * Kniha ho píše jako KernelTestCase nad databází z .env.test s rollbackem
 * přes dama/doctrine-test-bundle. Ukázka sestaví skutečný EntityManager
 * nad SQLite v paměti (SqliteUserManagement z ukázky kapitoly 10), takže
 * každý test začíná s prázdnou databází a kontejner nepotřebuje.
 */
final class DoctrineUserRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = SqliteUserManagement::entityManager();
        $this->repository    = new DoctrineUserRepository($this->entityManager);
    }

    public function testPersistsAndRetrievesUser(): void
    {
        $userId = UserId::generate();
        $email  = new Email('integrace@example.com');
        $user   = User::register($userId, new UserName('Test Uživatel'), $email, HashedPassword::fromPlainText('SilneHeslo123'));

        $this->repository->save($user);
        // save() jen persistuje; zápis do DB spouští až flush(). Vlastníkem
        // flushe je handler, takže si ho test musí zavolat sám.
        $this->entityManager->flush();
        $this->entityManager->clear(); // vyčistí identity map – jinak by čtení nešlo do DB

        $retrieved = $this->repository->findById($userId);

        $this->assertNotNull($retrieved);
        $this->assertTrue($userId->equals($retrieved->id));
        $this->assertTrue($email->equals($retrieved->email()));
    }

    public function testReturnsNullForNonExistentUser(): void
    {
        $this->assertNull($this->repository->findById(UserId::generate()));
    }

    public function testFindsByEmailAddress(): void
    {
        $email = new Email('hledat@example.com');
        $user  = User::register(UserId::generate(), new UserName('Test Uživatel'), $email, HashedPassword::fromPlainText('SilneHeslo123'));

        $this->repository->save($user);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $found = $this->repository->findByEmail($email);

        $this->assertNotNull($found);
        $this->assertTrue($email->equals($found->email()));
    }

    public function testFindByEmailSeesUserOnlyAfterFlush(): void
    {
        $email = new Email('exists@example.com');
        $user  = User::register(UserId::generate(), new UserName('Test Uživatel'), $email, HashedPassword::fromPlainText('SilneHeslo123'));

        // findOneBy() se ptá databáze; persistovaný, ale nezapsaný User v ní ještě není.
        $this->repository->save($user);
        $this->assertNull($this->repository->findByEmail($email));

        $this->entityManager->flush();

        $this->assertNotNull($this->repository->findByEmail($email));
    }
}
