<?php

declare(strict_types=1);
namespace App\Chapter08_Testing\UI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter08Controller extends AbstractController
{
    #[Route('/examples/testovani', name: 'chapter08')]
    public function index(): Response
    {
        return $this->render('examples/chapter08/index.html.twig', [
            'prev_route' => 'chapter07',
            'prev_title' => 'Ságy',
            'next_route' => 'chapter09',
            'next_title' => 'Migrace z CRUD na DDD',
        ]);
    }
}
