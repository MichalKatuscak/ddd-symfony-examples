<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\UI;

use App\Chapter03_BasicConcepts\Domain\Order\ProductId;
use App\Shared\Domain\Currency;
use App\Chapter03_BasicConcepts\Domain\Email;
use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Service\OrderConfirmationService;
use App\Chapter03_BasicConcepts\Infrastructure\Persistence\InMemoryOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter03Controller extends AbstractController
{
    #[Route('/examples/zakladni-koncepty', name: 'chapter03')]
    public function index(Request $request): Response
    {
        $order = Order::place(OrderId::generate(), 'student-1');
        $result = null;
        $error = null;
        $voResult = null;
        $voError = null;
        $events = [];

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            try {
                match ($action) {
                    'add_item' => (function () use ($order, $request, &$result, &$events) {
                        $order->addItem(
                            ProductId::generate(),
                            max(1, (int) $request->request->get('qty', 1)),
                            new Money(
                                (int) round((float) $request->request->get('price', '100') * 100),
                                Currency::CZK,
                            ),
                        );
                        $result = 'Položka přidána. Celkem: ' . $order->totalAmount()->formatted();
                        $events = $order->releaseEvents();
                        $events = array_map(function ($e) {
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
                        }, $events);
                    })(),
                    'confirm_with_item' => (function () use ($order, &$result, &$events) {
                        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));
                        $order->confirm();
                        $result = 'Objednávka potvrzena. Stav: ' . $order->status->value;
                        $events = $order->releaseEvents();
                        $events = array_map(function ($e) {
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
                        }, $events);
                    })(),
                    'confirm_empty' => (function () use ($order) {
                        $order->confirm();
                    })(),
                    'confirm_via_service' => (function () use ($order, &$result, &$events) {
                        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));
                        $repo = new InMemoryOrderRepository();
                        $service = new OrderConfirmationService($repo);
                        $service->confirm($order);
                        $result = 'Objednávka potvrzena přes Domain Service. Stav: ' . $order->status->value;
                        $events = $order->releaseEvents();
                        $events = array_map(function ($e) {
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
                        }, $events);
                    })(),
                    'vo_email' => (function () use ($request, &$voResult) {
                        $email = new Email($request->request->get('email', ''));
                        $voResult = ['type' => 'email', 'ok' => true, 'value' => (string) $email];
                    })(),
                    'vo_money' => (function () use ($request, &$voResult) {
                        $a = new Money((int) round((float) $request->request->get('amount_a', '0') * 100), 'CZK');
                        $b = new Money((int) round((float) $request->request->get('amount_b', '0') * 100), 'CZK');
                        $sum = $a->add($b);
                        $voResult = [
                            'type' => 'money',
                            'ok' => true,
                            'a' => $a->formatted(),
                            'b' => $b->formatted(),
                            'sum' => $sum->formatted(),
                            'immutable' => $a->formatted(),
                        ];
                    })(),
                    default => null,
                };
            } catch (\DomainException $e) {
                $error = 'DomainException: ' . $e->getMessage();
            } catch (\InvalidArgumentException $e) {
                if (in_array($action, ['vo_email', 'vo_money'])) {
                    $voError = 'InvalidArgumentException: ' . $e->getMessage();
                } else {
                    $error = 'InvalidArgumentException: ' . $e->getMessage();
                }
            }
        }

        return $this->render('examples/chapter03/index.html.twig', [
            'order' => $order,
            'result' => $result,
            'error' => $error,
            'voResult' => $voResult,
            'voError' => $voError,
            'events' => $events,
            'prev_route' => 'chapter01',
            'prev_title' => 'Co je DDD',
            'next_route' => 'chapter04',
            'next_title' => 'Implementace v Symfony',
        ]);
    }
}
