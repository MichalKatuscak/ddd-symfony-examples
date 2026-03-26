<?php

namespace App\Chapter03_BasicConcepts\UI;

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

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            try {
                match ($action) {
                    'add_item' => (function () use ($order, $request, &$result) {
                        $order->addItem(
                            $request->request->get('name', 'Produkt'),
                            max(1, (int) $request->request->get('qty', 1)),
                            new Money((int) ($request->request->get('price', 100) * 100), 'CZK'),
                        );
                        $result = 'Položka přidána. Celkem: ' . $order->total()->formatted();
                    })(),
                    'confirm_with_item' => (function () use ($order, &$result) {
                        $order->addItem('Demo produkt', 1, new Money(10000, 'CZK'));
                        $order->confirm();
                        $result = 'Objednávka potvrzena. Stav: ' . $order->status()->value();
                    })(),
                    'confirm_empty' => $order->confirm(),
                    default => null,
                };
            } catch (\DomainException $e) {
                $error = 'DomainException: ' . $e->getMessage();
            }
        }

        return $this->render('examples/chapter03/index.html.twig', [
            'order' => $order,
            'result' => $result,
            'error' => $error,
        ]);
    }
}
