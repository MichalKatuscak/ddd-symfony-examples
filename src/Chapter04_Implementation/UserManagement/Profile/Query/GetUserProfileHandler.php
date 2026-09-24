<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Profile\Query;

use App\Chapter04_Implementation\UserManagement\Domain\Repository\UserRepository;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Verze nad agregátem, jak ji ukazuje kapitola 10. Čte přes write model;
 * pro čtyři pole to stačí. Verzi nad vlastním read modelem ukazuje
 * kapitola o CQRS.
 */
#[AsMessageHandler(bus: 'messenger.bus.query')]
final readonly class GetUserProfileHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function __invoke(GetUserProfile $query): ?UserProfile
    {
        $user = $this->userRepository->findById(new UserId($query->userId));

        if ($user === null) {
            return null;
        }

        return new UserProfile(
            id: $user->id->value,
            name: $user->name()->value,
            email: $user->email()->value,
            createdAt: $user->createdAt,
        );
    }
}
