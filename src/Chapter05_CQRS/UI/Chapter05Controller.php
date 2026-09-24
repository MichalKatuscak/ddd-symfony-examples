<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\UI;

use App\Chapter05_CQRS\Ordering\Application\Command\PlaceOrder;
use App\Chapter05_CQRS\Ordering\Application\Query\ListOrders;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;
use App\Chapter05_CQRS\SharedKernel\Application\Query\QueryBus;
use App\UI\ExampleCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Post-Redirect-Get z kapitoly 12.12: zápis jde přes command bus,
 * po přesměrování se přehled čte z read modelu přes query bus.
 */
final class Chapter05Controller extends AbstractController
{
    // Kniha bere zákazníka z přihlášeného uživatele (#[CurrentUser]).
    // Ukázka přihlášení nemá, zákazníka volí formulář.
    private const array CUSTOMERS = [
        '01920000-0000-7000-8000-000000000001' => 'Zákaznice Jana',
        '01920000-0000-7000-8000-000000000002' => 'Zákazník Petr',
    ];

    // Ceník ukázky. Cenu posílá v commandu klient, stejně jako v knize.
    private const array PRODUCTS = [
        '01920000-0000-7000-8000-0000000000a1' => ['name' => 'Kniha o DDD', 'price' => 79900],
        '01920000-0000-7000-8000-0000000000a2' => ['name' => 'Workshop Event Storming', 'price' => 490000],
        '01920000-0000-7000-8000-0000000000a3' => ['name' => 'Samolepka', 'price' => 4900],
    ];

    public function __construct(
        #[Target('messenger.bus.command')] private readonly MessageBusInterface $commandBus,
        private readonly QueryBus $queryBus,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/examples/cqrs', name: 'chapter05')]
    public function index(Request $request): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            $customerId = $request->request->getString('customerId');
            $productId = $request->request->getString('productId');

            $command = new PlaceOrder(
                customerId: $customerId,
                items: [[
                    'productId' => $productId,
                    'quantity' => $request->request->getInt('quantity'),
                    'unitPriceInCents' => self::PRODUCTS[$productId]['price'] ?? -1,
                ]],
            );

            // V knize command validuje middleware `validation` na command
            // busu a kontroler chytá ValidationFailedException. Sběrnice
            // ukázek middleware nemá, proto validátor volá kontroler sám.
            foreach ($this->validator->validate($command) as $violation) {
                $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }

            if ($errors === []) {
                /** @var OrderId $orderId */
                $orderId = $this->commandBus->dispatch($command)
                    ->last(HandledStamp::class)
                    ->getResult();

                // Redirect – read model se mohl ještě neaktualizovat,
                // ale uživatel vidí potvrzení.
                $this->addFlash('success', sprintf('Objednávka %s byla vytvořena.', $orderId->value));

                return $this->redirectToRoute('chapter05', ['zakaznik' => $customerId]);
            }
        }

        $customerId = $request->query->getString('zakaznik');
        if (!isset(self::CUSTOMERS[$customerId])) {
            $customerId = array_key_first(self::CUSTOMERS);
        }

        return $this->render('examples/chapter05/index.html.twig', [
            'orders' => $this->queryBus->ask(new ListOrders($customerId)),
            'errors' => $errors,
            'customers' => self::CUSTOMERS,
            'products' => self::PRODUCTS,
            'customer_id' => $customerId,
            ...ExampleCatalog::navigation('chapter05'),
        ], new Response(status: $errors === [] ? 200 : 422));
    }
}
