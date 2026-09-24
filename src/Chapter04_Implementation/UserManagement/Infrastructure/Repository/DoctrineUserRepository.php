<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Infrastructure\Repository;

use App\Chapter04_Implementation\UserManagement\Domain\Model\User;
use App\Chapter04_Implementation\UserManagement\Domain\Repository\UserRepository;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

// Alias rozhraní → implementace nese atribut, ne services.yaml
// (kniha 10.14, „Symfony idiomy: #[AsAlias] pro repozitáře“).
#[AsAlias(id: UserRepository::class)]
final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(User $user): void
    {
        // Jen persist. Repozitář transakci ani flush neřídí: v knize je
        // obstará doctrine_transaction middleware command busu, v ukázce
        // RegisterUserHandler. Publikaci doménových událostí (releaseEvents())
        // zajišťuje aplikační vrstva až po flushi.
        $this->em->persist($user);
    }

    public function findById(UserId $id): ?User
    {
        // Identifikátor se předává jako hodnotový objekt, ne jako řetězec:
        // custom typ převádí jen instanci UserId, na cokoli jiného selže.
        return $this->em->find(User::class, $id);
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);
    }
}
