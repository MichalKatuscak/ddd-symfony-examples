<?php

declare(strict_types=1);
namespace App\Chapter07_Sagas\UI;
use App\Chapter07_Sagas\Application\OrderFulfillmentSaga;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter07Controller extends AbstractController
{
    #[Route('/examples/sagy', name: 'chapter07')]
    public function index(Request $request): Response
    {
        $log = [];
        if ($request->isMethod('POST')) {
            $saga = new OrderFulfillmentSaga();
            $log = $saga->execute(
                'order-' . rand(1, 999),
                stockAvailable: $request->request->getBoolean('stock', true),
                paymentSuccess: $request->request->getBoolean('payment', true),
            );
        }
        return $this->render('examples/chapter07/index.html.twig', ['log' => $log]);
    }
}
