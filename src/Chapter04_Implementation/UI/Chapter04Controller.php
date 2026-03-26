<?php

namespace App\Chapter04_Implementation\UI;

use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface;
use App\Chapter04_Implementation\Domain\Service\OrderPricingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter04Controller extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderPricingService $pricing,
    ) {}

    #[Route('/examples/implementace', name: 'chapter04')]
    public function index(Request $request): Response
    {
        $result = null;
        $dispatchedEvents = [];

        if ($request->isMethod('POST')) {
            $qty = max(1, (int) $request->request->get('qty', 1));
            $price = (int) ($request->request->get('price', 100) * 100);
            $discountedPrice = $this->pricing->applyVolumeDiscount(
                new Money($price, 'CZK'),
                $qty,
            );

            $order = Order::place(
                OrderId::generate(),
                'student-' . rand(1, 99),
                [['name' => $request->request->get('name', 'Produkt'), 'qty' => $qty, 'price' => $discountedPrice->amount]],
            );
            $this->orders->save($order);

            $rawEvents = $order->pullEvents();
            $dispatchedEvents = array_map(function ($e) {
                $ref = new \ReflectionClass($e);
                $payload = [];
                foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
                    $val = $prop->getValue($e);
                    $payload[$prop->getName()] = $val instanceof \DateTimeImmutable
                        ? $val->format('Y-m-d H:i:s')
                        : $val;
                }
                return [
                    'class' => $ref->getShortName(),
                    'occurredAt' => $e->occurredAt()->format('H:i:s'),
                    'payload' => $payload,
                ];
            }, $rawEvents);

            $result = sprintf(
                'Objednávka uložena. Celkem: %s.',
                $order->total()->formatted(),
            );
        }

        return $this->render('examples/chapter04/index.html.twig', [
            'orders' => $this->orders->findAll(),
            'result' => $result,
            'dispatchedEvents' => $dispatchedEvents,
        ]);
    }
}
