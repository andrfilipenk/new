<?php

namespace Project\Controller;

use Project\Service\ProjectService;
use Project\Model\Project;
use User\Controller\AbstractController;

/**
 * ProjectController
 * Handles project listing, detail view, and CRUD operations
 */
class ProjectController extends AbstractController
{
    protected $projectService;

    public function __construct()
    {
        $this->projectService = new ProjectService();
    }

    /**
     * Projects overview - card grid with filters
     */
    public function indexAction()
    {
        // Get filters from request
        $filters = [
            'status' => $this->request->get('status'),
            'client_id' => $this->request->get('client_id'),
            'search' => $this->request->get('search'),
            'date_from' => $this->request->get('date_from'),
            'date_to' => $this->request->get('date_to'),
        ];

        $page = (int) ($this->request->get('page') ?? 1);
        $perPage = (int) ($this->request->get('per_page') ?? 25);

        // Get projects with statistics
        $result = $this->projectService->listProjectsByFilter($filters, $page, $perPage);

        // Get total statistics
        $totalStats = [
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', Project::STATUS_ACTIVE)->count(),
            'completed_projects' => Project::where('status', Project::STATUS_COMPLETED)->count(),
        ];

        $this->getView()->setLayout('enterprise');
      


        return $this->render('project/overview/index', [
            'projects' => $result['projects'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'totalStats' => $totalStats,
            'statuses' => Project::getStatuses(),
        ]);
    }

    /**
     * Project detail view with tabs
     */
    public function detailAction()
    {
        $projectId = (int) $this->request->getParam('id');
        $activeTab = $this->request->get('tab') ?? 'overview';

        // Get project with all related data
        $data = $this->projectService->getProjectWithStatistics($projectId);

        // Get timeline data
        $timeline = $this->projectService->getProjectTimeline($projectId);

        // Get recent activity (last 10)
        $recentActivity = $data['activities']->take(10);



        return $this->render('project/detail/index', [
            'project' => $data['project'],
            'metrics' => $data['metrics'],
            'orders' => $data['orders'],
            'activities' => $data['activities'],
            'timeline' => $timeline,
            'recentActivity' => $recentActivity,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Create new project
     */
    public function createAction()
    {
        if ($this->request->isPost()) {
            $data = [
                'name' => $this->request->post('name'),
                'code' => $this->request->post('code'),
                'description' => $this->request->post('description'),
                'client_id' => $this->request->post('client_id'),
                'start_date' => $this->request->post('start_date'),
                'end_date' => $this->request->post('end_date'),
                'status' => $this->request->post('status') ?? Project::STATUS_PLANNING,
                'budget' => $this->request->post('budget'),
                'priority' => $this->request->post('priority') ?? Project::PRIORITY_MEDIUM,
                'created_by' => $this->session->get('user_id'),
            ];

            $project = new Project($data);
            
            if ($project->save()) {
                $this->session->setFlash('success', 'Project created successfully');
                $this->response->redirect('projects/' . $project->id);
                return;
            } else {
                $this->session->setFlash('error', 'Failed to create project');
            }
        }

        
        return $this->render('project/create', [
            'statuses' => Project::getStatuses(),
            'priorities' => Project::getPriorities(),
        ]);
    }

    /**
     * Update existing project
     */
    public function updateAction()
    {
        $projectId = (int) $this->request->getParam('id');
        $project = Project::findOrFail($projectId);

        if ($this->request->isPost()) {
            $data = [
                'name' => $this->request->post('name'),
                'code' => $this->request->post('code'),
                'description' => $this->request->post('description'),
                'client_id' => $this->request->post('client_id'),
                'start_date' => $this->request->post('start_date'),
                'end_date' => $this->request->post('end_date'),
                'status' => $this->request->post('status'),
                'budget' => $this->request->post('budget'),
                'priority' => $this->request->post('priority'),
                'updated_by' => $this->session->get('user_id'),
            ];

            $project->fill($data);
            
            if ($project->save()) {
                $this->session->setFlash('success', 'Project updated successfully');
                $this->response->redirect('projects/' . $project->id);
                return;
            } else {
                $this->session->setFlash('error', 'Failed to update project');
            }
        }
        return $this->render('project/edit', [
            'project' => $project,
            'statuses' => Project::getStatuses(),
            'priorities' => Project::getPriorities(),
        ]);
    }

    /**
     * Delete project
     */
    public function deleteAction()
    {
        if (!$this->request->isPost()) {
            $this->response->setStatusCode(405);
            return;
        }

        $projectId = (int) $this->request->getParam('id');
        $project = Project::findOrFail($projectId);

        if ($project->delete()) {
            $this->session->setFlash('success', 'Project deleted successfully');
            $this->response->redirect('projects');
        } else {
            $this->session->setFlash('error', 'Failed to delete project');
            $this->response->redirect('projects/' . $projectId);
        }
    }
}
