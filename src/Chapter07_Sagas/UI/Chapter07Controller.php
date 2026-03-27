<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\UI;

use App\Chapter07_Sagas\Application\Command\StartSagaCommand;
use App\Chapter07_Sagas\Application\SagaOrchestrator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter07Controller extends AbstractController
{
    #[Route('/examples/sagy', name: 'chapter07')]
    public function index(Request $request, SagaOrchestrator $orchestrator): Response
    {
        $saga = null;

        if ($request->isMethod('POST')) {
            $command = new StartSagaCommand(
                orderId: 'order-' . rand(1, 999),
                amount: 1500,
                stockAvailable: $request->request->getBoolean('stock', true),
                paymentSuccess: $request->request->getBoolean('payment', true),
                shipmentSuccess: $request->request->getBoolean('shipment', true),
            );

            $saga = $orchestrator->start($command);
        }

        return $this->render('examples/chapter07/index.html.twig', [
            'saga' => $saga,
            'prev_route' => 'chapter06',
            'prev_title' => 'Event Sourcing',
            'next_route' => 'chapter08',
            'next_title' => 'Testování',
        ]);
    }
}
