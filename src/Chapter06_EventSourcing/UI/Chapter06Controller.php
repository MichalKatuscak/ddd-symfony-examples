<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\UI;

use App\Chapter06_EventSourcing\Domain\Order\ConcurrencyException;
use App\Chapter06_EventSourcing\Domain\Order\Order;
use App\Chapter06_EventSourcing\Domain\Order\OrderId;
use App\Chapter06_EventSourcing\Infrastructure\EventStore\EventStoreInterface;
use App\Chapter06_EventSourcing\Infrastructure\Projection\OrderListProjection;
use App\Chapter06_EventSourcing\Infrastructure\Projection\OrderListProjector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter06Controller extends AbstractController
{
    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly OrderListProjector $projector,
        private readonly EntityManagerInterface $em,
    ) {}

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

            try {
                if ($action === 'place') {
                    $order = Order::place(
                        new OrderId($orderId),
                        'zákazník-1',
                        (int) round((float) $request->request->get('price', '599') * 100),
                    );
                    $uncommittedEvents = $order->pullUncommittedEvents();
                    $this->eventStore->append($orderId, $uncommittedEvents, 0);
                    $this->projector->project($uncommittedEvents);
                    $result = 'Objednávka zadána. ID: ' . substr($orderId, 0, 8) . '…';
                } elseif ($action === 'confirm') {
                    $events = $this->eventStore->load($orderId);
                    if (empty($events)) {
                        throw new \DomainException('Objednávka neexistuje: ' . $orderId);
                    }
                    $version = count($events);
                    $order = Order::reconstruct(new OrderId($orderId), $events);
                    $order->confirm();
                    $uncommittedEvents = $order->pullUncommittedEvents();
                    $this->eventStore->append($orderId, $uncommittedEvents, $version);
                    $this->projector->project($uncommittedEvents);
                    $result = 'Objednávka potvrzena. Rekonstruována z ' . count($events) . ' eventů.';
                } elseif ($action === 'cancel') {
                    $events = $this->eventStore->load($orderId);
                    if (empty($events)) {
                        throw new \DomainException('Objednávka neexistuje: ' . $orderId);
                    }
                    $version = count($events);
                    $order = Order::reconstruct(new OrderId($orderId), $events);
                    $order->cancel('Zákazník si to rozmyslel');
                    $uncommittedEvents = $order->pullUncommittedEvents();
                    $this->eventStore->append($orderId, $uncommittedEvents, $version);
                    $this->projector->project($uncommittedEvents);
                    $result = 'Objednávka zrušena.';
                }
            } catch (ConcurrencyException $e) {
                $result = 'Concurrency conflict: ' . $e->getMessage();
            } catch (\DomainException $e) {
                $result = 'Chyba: ' . $e->getMessage();
            }

            $history = $this->eventStore->load($currentOrderId);
        }

        $projections = $this->em->getRepository(OrderListProjection::class)->findBy([], ['updatedAt' => 'DESC']);

        return $this->render('examples/chapter06/index.html.twig', [
            'result' => $result,
            'history' => $history,
            'currentOrderId' => $currentOrderId,
            'projections' => $projections,
        ]);
    }
}
