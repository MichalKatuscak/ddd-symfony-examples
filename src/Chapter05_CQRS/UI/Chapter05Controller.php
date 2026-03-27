<?php

declare(strict_types=1);
namespace App\Chapter05_CQRS\UI;
use App\Chapter05_CQRS\Application\GetOrders\GetOrdersQuery;
use App\Chapter05_CQRS\Application\PlaceOrder\PlaceOrderCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter05Controller extends AbstractController
{
    public function __construct(
        #[Target('messenger.bus.command')] private readonly MessageBusInterface $commandBus,
        #[Target('messenger.bus.query')] private readonly MessageBusInterface $queryBus,
    ) {}

    #[Route('/examples/cqrs', name: 'chapter05')]
    public function index(Request $request): Response
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $envelope = $this->commandBus->dispatch(new PlaceOrderCommand(
                customerId: $request->request->get('customer', 'student-1'),
                items: [[
                    'name' => $request->request->get('product', 'Produkt'),
                    'qty' => max(1, (int) $request->request->get('qty', 1)),
                    'price' => (int) ($request->request->get('price', 100) * 100),
                ]],
            ));
            $orderId = $envelope->last(HandledStamp::class)?->getResult();
            $result = 'Objednávka zadána přes Command bus. ID: ' . substr((string)$orderId, 0, 8) . '…';
        }

        $envelope = $this->queryBus->dispatch(new GetOrdersQuery());
        $orders = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->render('examples/chapter05/index.html.twig', [
            'orders' => $orders,
            'result' => $result,
        ]);
    }
}
