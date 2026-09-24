<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UI;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\HashedPassword;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserName;
use App\Chapter09_Migration\CrudVersion\User as CrudUser;
use App\Chapter09_Migration\UserManagement\Domain\Model\User;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;
use App\Chapter09_Migration\UserManagement\Infrastructure\Legacy\Exception\UnmappableLegacyStatusException;
use App\Chapter09_Migration\UserManagement\Infrastructure\Legacy\LegacyUserTranslator;
use App\UI\ExampleCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter09Controller extends AbstractController
{
    #[Route('/examples/migrace-z-crud', name: 'chapter09')]
    public function index(Request $request): Response
    {
        $dddResult = null;
        $dddError = null;
        $crudResult = null;

        if ($request->isMethod('POST')) {
            try {
                match ($request->request->getString('action')) {
                    'crud_status' => $crudResult = $this->crudStatus(),
                    'crud_activate_twice' => $crudResult = $this->crudActivateTwice(),
                    'ddd_register' => $dddResult = $this->dddRegister(),
                    'ddd_forbidden' => Email::fromUserInput('jan@mailinator.com'),
                    'ddd_activate_twice' => $this->dddActivateTwice(),
                    'acl_translate' => $dddResult = $this->aclTranslate('banned'),
                    'acl_unknown' => $this->aclTranslate('suspended'),
                    default => null,
                };
            } catch (\DomainException|UnmappableLegacyStatusException $e) {
                $dddError = (new \ReflectionClass($e))->getShortName() . ': ' . $e->getMessage();
            }
        }

        return $this->render('examples/chapter09/index.html.twig', [
            'dddResult' => $dddResult,
            'dddError' => $dddError,
            'crudResult' => $crudResult,
            ...ExampleCatalog::navigation('chapter09'),
        ]);
    }

    private function crudStatus(): string
    {
        $user = new CrudUser();
        $user->setStatus('aktivni');

        return sprintf('CRUD: setStatus("aktivni") přijato bez chyby. Stav: %s.', $user->getStatus());
    }

    private function crudActivateTwice(): string
    {
        $user = new CrudUser();
        $user->setStatus('active');
        $user->setStatus('active');

        return 'CRUD: druhá „aktivace“ je jen další setStatus("active"). Nikdo se to nedozví.';
    }

    private function dddRegister(): string
    {
        $user = $this->register();
        $events = array_map(
            static fn (object $event): string => (new \ReflectionClass($event))->getShortName(),
            $user->releaseEvents(),
        );

        return sprintf(
            'DDD: User::register() – e-mail %s, stav %s, události: %s.',
            $user->email()->value,
            $user->status()->value,
            implode(', ', $events),
        );
    }

    private function dddActivateTwice(): void
    {
        $user = $this->register();
        $token = $user->verificationToken();
        $user->activate($token);
        $user->activate($token);
    }

    private function aclTranslate(string $legacyStatus): string
    {
        $user = (new LegacyUserTranslator())->toDomain([
            'uuid' => '018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03',
            'name' => 'Jan Novák',
            'email' => 'jan@firma.cz',
            'password' => '$2y$10$legacyhash',
            'status' => $legacyStatus,
            'created_at' => '2019-03-01 08:00:00',
            'verification_token' => null,
        ]);

        return sprintf(
            'ACL: legacy stav „%s“ → %s, registrace %s, události: %d.',
            $legacyStatus,
            $user->status()->name,
            $user->createdAt->format('j. n. Y'),
            count($user->releaseEvents()),
        );
    }

    private function register(): User
    {
        return User::register(
            UserId::generate(),
            new UserName('Jan Novák'),
            Email::fromUserInput(' Jan@Firma.cz '),
            HashedPassword::fromPlainText('SecurePass123'),
        );
    }
}
