<?php
// app/User/Controller/BoardController.php
namespace User\Controller;

use Core\Pagination\DatabasePaginator;
use Core\Pagination\PaginatorView;
use Intern\Model\TaskPriority;
use User\Model\Activity;
use User\Model\User;

class BoardController extends AbstractController
{

    public function indexAction()
    {
        /** @var User $user */
        $user = $this->getDI()->get('auth')->getUser();

        $page = (int) $this->getRequest()->get('page', 1);
        $paginator = DatabasePaginator::fromModel(\Intern\Model\Task::class)
        #$search = $this->getRequest()->get('search', '');
        //->when($search, fn($q) => $q->search($search, ['name', 'description']))
        ->orderBy('id', 'desc')
        ->where('assigned_to', $user->id)
        ->paginate(100, $page, $this->getRequest()->uri(), $this->getRequest()->get());

        if ($this->getRequest()->isAjax()) {
            return $this->getResponse()->json($paginator->toArray());
        }

        return $this->render('user/board/index', [
            'tasks'         => $paginator->items(),
            'totalTasks'    => $paginator->total(),
            'pagination'    => PaginatorView::make($paginator, ['view' => 'bootstrap']),
            'activities'    => Activity::getRecent(),
            'priorities'    => TaskPriority::all()
        ]);
    }
    
    private function getDemoTasks(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Project Meeting',
                'start' => date('Y-m-d', strtotime('+1 day')),
                'end' => date('Y-m-d', strtotime('+1 day')),
                'color' => '#007bff'
            ],
            [
                'id' => 2,
                'title' => 'Development Sprint',
                'start' => date('Y-m-d', strtotime('+3 days')),
                'end' => date('Y-m-d', strtotime('+15 days')),
                'color' => '#28a745'
            ],
            [
                'id' => 3,
                'title' => 'Client Review',
                'start' => date('Y-m-d', strtotime('+7 days')),
                'end' => date('Y-m-d', strtotime('+7 days')),
                'color' => '#ffc107'
            ],
            [
                'id' => 4,
                'title' => 'Testing Phase',
                'start' => date('Y-m-d', strtotime('+10 days')),
                'end' => date('Y-m-d', strtotime('+12 days')),
                'color' => '#dc3545'
            ]
        ];
    }
}