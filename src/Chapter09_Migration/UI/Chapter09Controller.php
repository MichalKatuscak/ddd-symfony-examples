<?php
namespace App\Chapter09_Migration\UI;
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
        $result = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            try {
                $task = Task::create(TaskId::generate(), 'Refaktorovat controller', 'projekt-1');
                if ($action === 'valid') {
                    $task->start('member-1');
                    $task->complete();
                    $result = 'DDD Task: todo → in_progress → done ✓';
                } elseif ($action === 'invalid') {
                    $task->complete();
                }
            } catch (\DomainException $e) {
                $error = 'DomainException zachycena: ' . $e->getMessage();
            }
        }

        return $this->render('examples/chapter09/index.html.twig', [
            'result' => $result,
            'error' => $error,
        ]);
    }
}
