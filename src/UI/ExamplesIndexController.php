<?php

declare(strict_types=1);

namespace App\UI;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExamplesIndexController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function homepage(): RedirectResponse
    {
        return $this->redirectToRoute('examples_index');
    }

    #[Route('/examples', name: 'examples_index')]
    public function index(): Response
    {
        return $this->render('examples/index.html.twig', [
            'chapters' => ExampleCatalog::all(),
            'book_url' => ExampleCatalog::bookUrl(),
        ]);
    }
}
