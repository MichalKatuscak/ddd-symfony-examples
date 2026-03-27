<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UI;

use App\Chapter04_Implementation\Application\GetOrders\GetOrdersQuery;
use App\Chapter04_Implementation\Application\PlaceOrder\PlaceOrderCommand;
use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Order\OrderLine;
use App\Chapter04_Implementation\Domain\Service\OrderPricingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter04Controller extends AbstractController
{
    public function __construct(
        #[Target('messenger.bus.command')] private readonly MessageBusInterface $commandBus,
        #[Target('messenger.bus.query')] private readonly MessageBusInterface $queryBus,
        private readonly OrderPricingService $pricing,
    ) {}

    #[Route('/examples/implementace', name: 'chapter04')]
    public function index(Request $request): Response
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $qty = max(1, (int) $request->request->get('qty', 1));
            $price = (int) round((float) $request->request->get('price', '100') * 100);
            $discountedPrice = $this->pricing->applyVolumeDiscount(
                new Money($price, 'CZK'),
                $qty,
            );

            $envelope = $this->commandBus->dispatch(new PlaceOrderCommand(
                customerId: 'student-' . random_int(1, 99),
                lines: [new OrderLine($request->request->get('name', 'Produkt'), $qty, $discountedPrice)],
            ));
            $orderId = $envelope->last(HandledStamp::class)?->getResult();
            $result = sprintf('Objednávka uložena. ID: %s…', substr((string) $orderId, 0, 8));
        }

        $envelope = $this->queryBus->dispatch(new GetOrdersQuery());
        $orders = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->render('examples/chapter04/index.html.twig', [
            'orders' => $orders,
            'result' => $result,
            'dispatchedEvents' => [],
            'prev_route' => 'chapter03',
            'prev_title' => 'Základní koncepty DDD',
            'next_route' => 'chapter05',
            'next_title' => 'CQRS',
        ]);
    }
}
