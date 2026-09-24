<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\UI;

use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaRepository;
use App\Chapter07_Sagas\Payment\Infrastructure\InMemoryPaymentGateway;
use App\Chapter07_Sagas\Shipping\Infrastructure\InMemoryShippingService;
use App\Chapter07_Sagas\Warehouse\Infrastructure\InMemoryStockService;
use App\Chapter11_OutboxPattern\Ordering\Application\Command\PlaceOrder;
use App\Chapter11_OutboxPattern\Ordering\Application\Handler\PlaceOrderHandler;
use App\Chapter11_OutboxPattern\Ordering\Domain\Repository\OrderRepository;
use App\Chapter11_OutboxPattern\Outbox\Application\OutboxMessageFactory;
use App\Chapter11_OutboxPattern\Outbox\Application\OutboxRepository;
use App\UI\ExampleCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class Chapter07Controller extends AbstractController
{
    public function __construct(
        private readonly PlaceOrderHandler $placeOrder,
        private readonly OutboxRepository $outbox,
        private readonly OutboxMessageFactory $outboxFactory,
        #[Target('event.bus')]
        private readonly MessageBusInterface $eventBus,
        private readonly OrderRepository $orders,
        private readonly OrderSagaRepository $sagas,
        private readonly InMemoryPaymentGateway $paymentGateway,
        private readonly InMemoryStockService $stock,
        private readonly InMemoryShippingService $shipping,
    ) {}

    #[Route('/examples/sagy', name: 'chapter07')]
    public function index(Request $request): Response
    {
        $result = null;

        if ($request->isMethod('POST')) {
            // Přepínače, které kniha čte z PAYMENT_FAILS a STOCK_FAILS.
            $this->paymentGateway->failAlways($request->request->getBoolean('payment_fails'));
            $this->stock->failAlways($request->request->getBoolean('stock_fails'));

            // Objednávka vzniká jako v kapitole Outbox Pattern: placeWithItems()
            // ji potvrdí a zamkne a handler zapíše integrační událost do outboxu.
            $orderId = ($this->placeOrder)(new PlaceOrder(
                customerId: (string) Uuid::v7(),
                items: [
                    ['productId' => (string) Uuid::v7(), 'quantity' => 2, 'unitPriceInCents' => 750_00],
                ],
            ));

            // Roli relaye zde hraje controller: zprávu z outboxu pošle na
            // event.bus, kde ji odebírá OrderProcessManager. Sběrnice je
            // synchronní, takže celá sága doběhne ještě v tomto volání.
            foreach ($this->outbox->fetchPending() as $row) {
                $this->eventBus->dispatch($this->outboxFactory->reconstitute($row));
                $this->outbox->markSent($row->id);
            }

            $saga = $this->sagas->findByCorrelationId($orderId->value);
            $order = $this->orders->get($orderId);
            $shipmentId = $saga?->context()['shipmentId'] ?? null;

            $result = [
                'orderId' => $orderId->value,
                'saga' => $saga,
                'order' => $order,
                'stockReserved' => $this->stock->isReserved($orderId->value),
                'shipmentCancelled' => $shipmentId !== null && $this->shipping->isCancelled($shipmentId),
            ];
        }

        return $this->render('examples/chapter07/index.html.twig', [
            'result' => $result,
            ...ExampleCatalog::navigation('chapter07'),
        ]);
    }
}
