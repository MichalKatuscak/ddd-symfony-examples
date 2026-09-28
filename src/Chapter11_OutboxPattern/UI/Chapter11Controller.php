<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\UI;

use App\Chapter11_OutboxPattern\Inbox\Infrastructure\InMemoryInboxRepository;
use App\Chapter11_OutboxPattern\Ordering\Application\Command\PlaceOrder;
use App\Chapter11_OutboxPattern\Ordering\Application\Handler\PlaceOrderHandler;
use App\Chapter11_OutboxPattern\Outbox\Application\OutboxRelay;
use App\Chapter11_OutboxPattern\Outbox\Domain\OutboxMessage;
use App\Chapter11_OutboxPattern\Outbox\Infrastructure\InMemoryOutboxRepository;
use App\Chapter11_OutboxPattern\Outbox\Infrastructure\InProcessPublisher;
use App\Chapter11_OutboxPattern\Reporting\Application\Subscriber\OrderPlacedReadModelUpdater;
use App\Chapter11_OutboxPattern\Reporting\Infrastructure\InMemoryReadModelStore;
use App\UI\ExampleCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Celý cyklus (zápis → relay → případný výpadek brokera → opakované
 * doručení) běží v jednom HTTP requestu nad úložišti v paměti. Controller
 * po každém kroku zachytí snímek outboxu, inboxu a read modelu.
 */
final class Chapter11Controller extends AbstractController
{
    public function __construct(
        private readonly PlaceOrderHandler $placeOrder,
        private readonly OutboxRelay $relay,
        private readonly InProcessPublisher $publisher,
        private readonly InMemoryOutboxRepository $outbox,
        private readonly InMemoryInboxRepository $inbox,
        private readonly InMemoryReadModelStore $readModel,
    ) {}

    #[Route('/examples/outbox', name: 'chapter11')]
    public function index(Request $request): Response
    {
        $log = [];
        $stages = [];

        if ($request->isMethod('POST')) {
            $quantity = max(1, $request->request->getInt('quantity', 2));
            $unitPrice = max(0, (int) round((float) $request->request->get('price', '750') * 100));
            $brokerDown = $request->request->getBoolean('broker_down');
            $redeliver = $request->request->getBoolean('redeliver');

            // 1) Objednávka a outbox řádek vznikají v jednom zápisu.
            $orderId = ($this->placeOrder)(new PlaceOrder(
                customerId: (string) Uuid::v7(),
                items: [
                    ['productId' => (string) Uuid::v7(), 'quantity' => $quantity, 'unitPriceInCents' => $unitPrice],
                ],
            ));
            $log[] = sprintf('PlaceOrderHandler: objednávka %s… uložena, v outboxu 1 pending řádek', substr($orderId->value, 0, 8));
            $stages[] = $this->snapshot('Po PlaceOrder (relay ještě neběžel)');

            // 2) Volitelně: broker při prvním průchodu relaye nepřijme spojení.
            if ($brokerDown) {
                $this->publisher->simulateOutage();
                $result = $this->relay->dispatchPending();
                $log[] = sprintf(
                    'OutboxRelay: broker nedostupný (TransportException) – publikováno %d, průchod přerušen, pokus se řádku nepočítá',
                    $result['processed'],
                );
                $stages[] = $this->snapshot('Po výpadku brokera (řádek zůstává pending, attempts beze změny)');

                // Worker by teď čekal s backoffem 1 s, 2 s, 4 s … Ukázka nečeká,
                // broker rovnou „naskočí“.
                $this->publisher->simulateOutage(false);
                $log[] = 'Worker po backoffu zkouší znovu (v ukázce bez čekání), broker už odpovídá';
            }

            // 3) Relay publikuje pending řádky a označí je jako sent.
            $result = $this->relay->dispatchPending();
            $log[] = sprintf('OutboxRelay: publikováno %d, selhání %d', $result['processed'], $result['failed']);
            $stages[] = $this->snapshot('Po průchodu relaye');

            // 4) Volitelně: relay spadl mezi publishem a markSent. Po restartu
            //    vidí řádek jako pending a pošle ho znovu – at-least-once.
            if ($redeliver) {
                foreach ($this->outbox->all() as $message) {
                    if ($message->status === 'sent') {
                        $message->status = 'pending';
                        $message->sentAt = null;
                    }
                }
                $result = $this->relay->dispatchPending();
                $log[] = sprintf('Opakované doručení: relay publikoval znovu (%d), inbox duplicitu zahodil', $result['processed']);
                $stages[] = $this->snapshot('Po opakovaném doručení (inbox deduplikuje)');
            }
        }

        return $this->render('examples/chapter11/index.html.twig', [
            'log' => $log,
            'stages' => $stages,
            'consumer' => OrderPlacedReadModelUpdater::CONSUMER,
            ...ExampleCatalog::navigation('chapter11'),
        ]);
    }

    /**
     * Snímek jako pole skalárů, aby ho pozdější změny řádků nepřepsaly.
     *
     * @return array{label: string, outbox: list<array<string, mixed>>, inbox: list<array<string, string>>, readModel: list<array<string, mixed>>}
     */
    private function snapshot(string $label): array
    {
        return [
            'label' => $label,
            'outbox' => array_map(
                static fn (OutboxMessage $m): array => [
                    'id' => (string) $m->id,
                    'messageType' => $m->messageType,
                    'aggregateId' => $m->aggregateId,
                    'status' => $m->status,
                    'attempts' => $m->attempts,
                    'availableAt' => $m->availableAt->format('H:i:s'),
                    'lastError' => $m->lastError,
                ],
                $this->outbox->all(),
            ),
            'inbox' => array_map(
                static fn (array $row): array => [
                    'eventId' => $row['eventId'],
                    'consumer' => $row['consumer'],
                    'processedAt' => $row['processedAt']->format('H:i:s'),
                ],
                $this->inbox->all(),
            ),
            'readModel' => array_map(
                static fn (array $row): array => [
                    'orderId' => $row['orderId'],
                    'items' => count($row['items']),
                    'writes' => $row['writes'],
                ],
                $this->readModel->all(),
            ),
        ];
    }
}
