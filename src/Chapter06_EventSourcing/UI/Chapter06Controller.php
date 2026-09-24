<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\UI;

use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\ConcurrencyException;
use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\EventStore;
use App\Chapter06_EventSourcing\Infrastructure\Ordering\EventSourcedOrderRepository;
use App\Chapter06_EventSourcing\Infrastructure\Ordering\Projection\OrderSummaryProjector;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Order;
use App\Chapter06_EventSourcing\Ordering\EventSourced\OrderItem;
use App\UI\ExampleCatalog;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class Chapter06Controller extends AbstractController
{
    public function __construct(
        private readonly EventSourcedOrderRepository $orders,
        private readonly EventStore $eventStore,
        #[Target('event.bus')]
        private readonly MessageBusInterface $eventBus,
        private readonly Connection $connection,
    ) {}

    #[Route('/examples/event-sourcing', name: 'chapter06')]
    public function index(Request $request): Response
    {
        $result = null;
        $error = null;
        $orderId = (string) $request->request->get('order_id', '');

        if ($request->isMethod('POST')) {
            try {
                $result = match ((string) $request->request->get('action')) {
                    'place' => $this->place($request, $orderId),
                    'add_item' => $this->addItem($request, $orderId),
                    'confirm' => $this->confirm($orderId),
                    'ship' => $this->ship($orderId),
                    'conflict' => $this->conflict($orderId),
                    default => null,
                };
            } catch (ConcurrencyException $e) {
                $error = 'Konflikt verzí: ' . $e->getMessage();
            } catch (\DomainException $e) {
                $error = 'Doménové pravidlo: ' . $e->getMessage();
            }
        }

        $order = null;
        $stream = [];
        if (Uuid::isValid($orderId)) {
            $stream = $this->eventStore->loadStream($orderId);
            $order = $stream === [] ? null : $this->orders->load($orderId);
        }

        return $this->render('examples/chapter06/index.html.twig', [
            'result' => $result,
            'error' => $error,
            'orderId' => $order !== null ? $orderId : null,
            'order' => $order,
            'stream' => $stream,
            'summaries' => $this->connection->fetchAllAssociative(
                'SELECT * FROM ' . OrderSummaryProjector::TABLE . ' ORDER BY placed_at DESC LIMIT 10',
            ),
            ...ExampleCatalog::navigation('chapter06'),
        ]);
    }

    private function place(Request $request, string &$orderId): string
    {
        $orderId = (string) Uuid::v7();
        $order = Order::place($orderId, (string) Uuid::v7());
        $order->addItem($this->itemFrom($request));
        $this->store($order);

        return 'Objednávka založena: OrderPlaced a OrderItemAdded ve verzích 1 a 2.';
    }

    private function addItem(Request $request, string $orderId): string
    {
        $order = $this->orders->load($orderId);
        $order->addItem($this->itemFrom($request));
        $this->store($order);

        return sprintf('Položka přidána. Agregát je ve verzi %d.', $order->version());
    }

    private function confirm(string $orderId): string
    {
        $order = $this->orders->load($orderId);
        $order->confirm();
        $this->store($order);

        return 'Objednávka potvrzena.';
    }

    private function ship(string $orderId): string
    {
        $order = $this->orders->load($orderId);
        $order->ship('DPD-' . random_int(100000, 999999));
        $this->store($order);

        return 'Objednávka odeslána.';
    }

    /**
     * Dva procesy načtou stejnou verzi streamu a oba zapisují. První projde,
     * druhý narazí na unikátní index (aggregate_id, version).
     */
    private function conflict(string $orderId): string
    {
        $first = $this->orders->load($orderId);
        $second = $this->orders->load($orderId);

        $first->addItem(new OrderItem((string) Uuid::v7(), 1, 100_00));
        $this->store($first);

        $second->addItem(new OrderItem((string) Uuid::v7(), 1, 200_00));
        $this->store($second); // ConcurrencyException

        return 'Nenastane – druhý zápis vždy selže.';
    }

    /**
     * Zápis do Event Store, pak synchronní doručení projekci. Kniha nechává
     * události z event_store číst relay (sekce 13.08); ukázka ho nemá, proto
     * je po úspěšném zápisu pošle na event.bus sama.
     */
    private function store(Order $order): void
    {
        $events = $order->recordedEvents();
        $this->orders->save($order);

        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }

    private function itemFrom(Request $request): OrderItem
    {
        return new OrderItem(
            (string) Uuid::v7(),
            max(1, $request->request->getInt('quantity', 1)),
            max(0, (int) round((float) $request->request->get('price', '599') * 100)),
        );
    }
}
