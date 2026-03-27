<?php

declare(strict_types=1);
namespace App\Chapter09_Migration\UI;
use App\Chapter09_Migration\CrudVersion\TaskController as CrudTaskController;
use App\Chapter09_Migration\Domain\Task\Task;
use App\Chapter09_Migration\Domain\Task\TaskId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter09Controller extends AbstractController
{
    #[Route('/examples/migrace-z-crud', name: 'chapter09')]
    public function index(Request $request): Response
    {
        $dddResult = null;
        $dddError = null;
        $crudResult = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'ddd_valid') {
                try {
                    $task = Task::create(TaskId::generate(), 'Refaktorovat controller', 'projekt-1');
                    $task->start('member-1');
                    $task->complete();
                    $dddResult = 'DDD Task: todo → in_progress → done ✓ (stav: ' . $task->status()->value . ')';
                } catch (\DomainException $e) {
                    $dddError = 'DomainException zachycena: ' . $e->getMessage();
                }
            } elseif ($action === 'ddd_invalid') {
                try {
                    $task = Task::create(TaskId::generate(), 'Refaktorovat controller', 'projekt-1');
                    $task->complete();
                } catch (\DomainException $e) {
                    $dddError = 'DomainException zachycena: ' . $e->getMessage();
                }
            } elseif ($action === 'crud_skip') {
                $crud = new CrudTaskController();
                $data = $crud->completeWithoutStart();
                $crudResult = 'CRUD: setStatus("done") přeskočilo todo→in_progress. Stav: ' . $data['task']->getStatus() . ' — žádná chyba!';
            } elseif ($action === 'crud_invalid') {
                $crud = new CrudTaskController();
                $data = $crud->setInvalidStatus();
                $crudResult = 'CRUD: setStatus("banana") přijato bez chyby. Stav: ' . $data['task']->getStatus() . ' — žádná validace!';
            }
        }

        return $this->render('examples/chapter09/index.html.twig', [
            'dddResult' => $dddResult,
            'dddError' => $dddError,
            'crudResult' => $crudResult,
            'prev_route' => 'chapter08',
            'prev_title' => 'Testování',
            'next_route' => null,
            'next_title' => null,
        ]);
    }
}
