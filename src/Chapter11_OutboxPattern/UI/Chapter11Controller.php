<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\UI;

use App\Chapter11_OutboxPattern\Application\DispatchOutbox\OutboxRelay;
use App\Chapter11_OutboxPattern\Application\PlaceOrder\PlaceOrderCommand;
use App\Chapter11_OutboxPattern\Application\PlaceOrder\PlaceOrderHandler;
use App\Chapter11_OutboxPattern\Domain\Inbox\InboxRepositoryInterface;
use App\Chapter11_OutboxPattern\Domain\Order\OrderRepositoryInterface;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxRepositoryInterface;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxStatus;
use App\Chapter11_OutboxPattern\Infrastructure\InMemoryReadModelStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Demo controller — celý cyklus (place → relay → redeliver) běží v rámci
 * jednoho HTTP requestu. In-memory repozitáře nepřežijí request, ale ukázku
 * to neomezuje: scénář si volíš formulářem a controller pak složí výslednou
 * tabulku stavů a zobrazí ji.
 */
final class Chapter11Controller extends AbstractController
{
    public function __construct(
        private readonly PlaceOrderHandler $placeOrder,
        private readonly OutboxRelay $relay,
        private readonly OrderRepositoryInterface $orders,
        private readonly OutboxRepositoryInterface $outbox,
        private readonly InboxRepositoryInterface $inbox,
        private readonly InMemoryReadModelStore $readModel,
    ) {
    }

    #[Route('/examples/outbox', name: 'chapter11')]
    public function index(Request $request): Response
    {
        $log = [];
        $stages = [];

        if ($request->isMethod('POST')) {
            $customer = (string) $request->request->get('customer', 'student-1');
            $amount = max(1, (int) $request->request->get('amount', 1500));
            $runRelay = $request->request->getBoolean('run_relay', true);
            $simulateRedelivery = $request->request->getBoolean('redeliver', false);

            // 1) Place order — atomicky uloží Order + outbox řádek.
            $orderId = ($this->placeOrder)(new PlaceOrderCommand(
                customerId: $customer,
                amount: $amount,
            ));
            $log[] = sprintf('PlaceOrderHandler: uložen Order %s… + 1 pending outbox row', substr($orderId, 0, 8));
            $stages[] = $this->snapshot('Po PlaceOrder (před relayem)');

            // 2) Relay — vezme pending zprávy a doručí je subscriberům.
            if ($runRelay) {
                $result = $this->relay->dispatchPending();
                $log[] = sprintf('OutboxRelay: publikováno %d, selhání %d', $result['processed'], $result['failed']);
                $stages[] = $this->snapshot('Po prvním relayi');

                // 3) Simulace at-least-once delivery: označíme všechny sent zprávy
                //    znovu jako pending a relay je doručí podruhé. Inbox by měl
                //    duplikáty zahodit a updatesApplied zůstat 1.
                if ($simulateRedelivery) {
                    foreach ($this->outbox->findAll() as $message) {
                        if ($message->status() === OutboxStatus::Sent) {
                            $reset = new \ReflectionProperty($message, 'status');
                            $reset->setValue($message, OutboxStatus::Pending);
                            $this->outbox->save($message);
                        }
                    }
                    $result = $this->relay->dispatchPending();
                    $log[] = sprintf('Duplicitní doručení: %d zpráv znovu prošlo relayem', $result['processed']);
                    $stages[] = $this->snapshot('Po duplicitním doručení (Inbox musí deduplikovat)');
                }
            }
        }

        return $this->render('examples/chapter11/index.html.twig', [
            'log' => $log,
            'stages' => $stages,
            'prev_route' => 'chapter09',
            'prev_title' => 'Migrace z CRUD',
            'next_route' => null,
            'next_title' => null,
        ]);
    }

    /**
     * @return array{label: string, outbox: array, inbox: array, read_model: array}
     */
    private function snapshot(string $label): array
    {
        // Deep copy přes serializaci, aby pozdější mutace OutboxMessage
        // nepřepsaly už zachycené snímky stavů.
        return [
            'label' => $label,
            'outbox' => array_map(
                static fn ($m): array => [
                    'id' => $m->id,
                    'type' => $m->type,
                    'status' => $m->status()->value,
                    'attempts' => $m->attempts(),
                ],
                $this->outbox->findAll(),
            ),
            'inbox' => array_map(
                static fn ($p): array => [
                    'consumerName' => $p->consumerName,
                    'eventId' => $p->eventId,
                    'processedAt' => $p->processedAt->format(\DateTimeInterface::ATOM),
                ],
                $this->inbox->findAll(),
            ),
            'read_model' => array_map(
                static fn (array $r): array => $r,
                $this->readModel->findAll(),
            ),
        ];
    }
}
