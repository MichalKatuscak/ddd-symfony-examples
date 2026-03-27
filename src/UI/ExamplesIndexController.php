<?php

declare(strict_types=1);

namespace App\UI;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExamplesIndexController extends AbstractController
{
    #[Route('/examples', name: 'examples_index')]
    public function index(): Response
    {
        return $this->render('examples/index.html.twig', [
            'chapters' => [
                ['route' => 'chapter01', 'num' => 1,  'title' => 'Co je DDD',               'desc' => 'Čistý doménový model — košík bez DB'],
                ['route' => 'chapter03', 'num' => 3,  'title' => 'Základní koncepty',        'desc' => 'Entity, Value Objects, Agregát'],
                ['route' => 'chapter04', 'num' => 4,  'title' => 'Implementace v Symfony',   'desc' => 'Doctrine, Domain Events, Domain Service'],
                ['route' => 'chapter05', 'num' => 5,  'title' => 'CQRS',                     'desc' => 'Commands, Queries, Symfony Messenger'],
                ['route' => 'chapter06', 'num' => 6,  'title' => 'Event Sourcing',            'desc' => 'EventStore, rekonstrukce ze eventů'],
                ['route' => 'chapter07', 'num' => 7,  'title' => 'Ságy',                     'desc' => 'Orchestrace, kompenzační transakce'],
                ['route' => 'chapter08', 'num' => 8,  'title' => 'Testování',                'desc' => 'Unit testy doménových tříd'],
                ['route' => 'chapter09', 'num' => 9,  'title' => 'Migrace z CRUD',           'desc' => 'Ochrana invariantů vs. settery'],
            ],
        ]);
    }
}
