<?php

declare(strict_types=1);
namespace App\Chapter06_EventSourcing\UI;

use App\Chapter06_EventSourcing\Domain\Order\Order;
use App\Chapter06_EventSourcing\Domain\Order\OrderId;
use App\Chapter06_EventSourcing\Infrastructure\EventStore\DoctrineEventStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter06Controller extends AbstractController
{
    public function __construct(private readonly DoctrineEventStore $eventStore) {}

    #[Route('/examples/event-sourcing', name: 'chapter06')]
    public function index(Request $request): Response
    {
        $result = null;
        $history = [];
        $currentOrderId = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $orderId = $request->request->get('order_id') ?: OrderId::generate()->value;
            $currentOrderId = $orderId;

            if ($action === 'place') {
                $order = Order::place(
                    new OrderId($orderId),
                    'zákazník-1',
                    (int) round((float) $request->request->get('price', '599') * 100),
                );
                $this->eventStore->append($orderId, $order->pullUncommittedEvents());
                $result = 'Objednávka zadána. ID: ' . substr($orderId, 0, 8) . '…';
            } elseif ($action === 'confirm') {
                $events = $this->eventStore->load($orderId);
                $order = Order::reconstruct(new OrderId($orderId), $events);
                $order->confirm();
                $this->eventStore->append($orderId, $order->pullUncommittedEvents());
                $result = 'Objednávka potvrzena. Rekonstruována z ' . count($events) . ' eventů.';
            } elseif ($action === 'cancel') {
                $events = $this->eventStore->load($orderId);
                $order = Order::reconstruct(new OrderId($orderId), $events);
                $order->cancel('Zákazník si to rozmyslel');
                $this->eventStore->append($orderId, $order->pullUncommittedEvents());
                $result = 'Objednávka zrušena.';
            }

            $history = $this->eventStore->load($currentOrderId);
        }

        return $this->render('examples/chapter06/index.html.twig', [
            'result' => $result,
            'history' => $history,
            'currentOrderId' => $currentOrderId,
        ]);
    }
}
