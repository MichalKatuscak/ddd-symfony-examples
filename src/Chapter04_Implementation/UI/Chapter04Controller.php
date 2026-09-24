<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UI;

use App\Chapter04_Implementation\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter04_Implementation\UserManagement\Domain\Exception\DuplicateEmailException;
use App\Chapter04_Implementation\UserManagement\Profile\Query\GetUserProfile;
use App\Chapter04_Implementation\UserManagement\Profile\Query\UserProfile;
use App\Chapter04_Implementation\UserManagement\Registration\Command\RegisterUser;
use App\Chapter04_Implementation\UserManagement\Registration\Form\RegistrationFormType;
use App\UI\ExampleCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Chapter04Controller extends AbstractController
{
    public function __construct(
        #[Target('messenger.bus.command')] private readonly MessageBusInterface $commandBus,
        #[Target('messenger.bus.query')] private readonly MessageBusInterface $queryBus,
        private readonly ValidatorInterface $validator,
        private readonly RegisteredUserRecorder $registrations,
    ) {}

    #[Route('/examples/implementace', name: 'chapter04')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $command = new RegisterUser($data['name'], $data['email'], $data['password']);

            // V knize command validuje middleware `validation` na sběrnici.
            // Command bus ukázek ho nemá, proto kontroler volá validátor sám
            // a porušení vrací k polím formuláře stejně jako kniha.
            $violations = $this->validator->validate($command);

            foreach ($violations as $violation) {
                $form->get($violation->getPropertyPath())->addError(
                    new FormError((string) $violation->getMessage()),
                );
            }

            if (count($violations) === 0) {
                try {
                    $this->commandBus->dispatch($command);

                    $event = $this->registrations->last();
                    $this->addFlash('success', 'Účet byl vytvořen.');

                    if ($event !== null) {
                        $this->addFlash('event', json_encode([
                            'userId' => $event->userId,
                            'email' => $event->email,
                            'occurredAt' => $event->occurredAt->format(\DATE_ATOM),
                        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));
                    }

                    return $this->redirectToRoute('chapter04', ['profil' => $event?->userId]);
                } catch (HandlerFailedException $e) {
                    // Synchronní Messenger výjimku z handleru balí – catch
                    // (DuplicateEmailException) kolem dispatch() by nic nechytil.
                    $duplicates = $e->getWrappedExceptions(DuplicateEmailException::class);

                    if ($duplicates === []) {
                        throw $e; // neznámou chybu nemaskovat
                    }

                    $this->addFlash('error', reset($duplicates)->getMessage());
                }
            }
        }

        return $this->render('examples/chapter04/index.html.twig', [
            'form' => $form,
            'profile' => $this->profile($request->query->getString('profil')),
            'statuses' => OrderStatus::cases(),
            ...ExampleCatalog::navigation('chapter04'),
        ]);
    }

    private function profile(string $userId): ?UserProfile
    {
        if (!Uuid::isValid($userId)) {
            return null;
        }

        return $this->queryBus->dispatch(new GetUserProfile($userId))
            ->last(HandledStamp::class)
            ?->getResult();
    }
}
