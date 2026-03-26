<?php

namespace App\Chapter03_BasicConcepts\UI;

use App\Chapter03_BasicConcepts\Domain\Email;
use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter03Controller extends AbstractController
{
    #[Route('/examples/zakladni-koncepty', name: 'chapter03')]
    public function index(Request $request): Response
    {
        $order = Order::create(OrderId::generate(), 'student-1');
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
                            $request->request->get('name', 'Produkt'),
                            max(1, (int) $request->request->get('qty', 1)),
                            new Money((int) ($request->request->get('price', 100) * 100), 'CZK'),
                        );
                        $result = 'Položka přidána. Celkem: ' . $order->total()->formatted();
                        $events = $order->pullEvents();
                    })(),
                    'confirm_with_item' => (function () use ($order, &$result, &$events) {
                        $order->addItem('Demo produkt', 1, new Money(10000, 'CZK'));
                        $order->confirm();
                        $result = 'Objednávka potvrzena. Stav: ' . $order->status()->value();
                        $events = $order->pullEvents();
                    })(),
                    'confirm_empty' => (function () use ($order) {
                        $order->confirm();
                    })(),
                    'vo_email' => (function () use ($request, &$voResult) {
                        $email = new Email($request->request->get('email', ''));
                        $voResult = ['type' => 'email', 'ok' => true, 'value' => (string) $email];
                    })(),
                    'vo_money' => (function () use ($request, &$voResult) {
                        $a = new Money((int) ($request->request->get('amount_a', 0) * 100), 'CZK');
                        $b = new Money((int) ($request->request->get('amount_b', 0) * 100), 'CZK');
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
        ]);
    }
}
