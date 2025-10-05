<?php
// app/Intern/Controller/Kanban.php
namespace Intern\Controller;

use Core\Mvc\Controller;
use Core\Http\Response;
use Intern\Service\KanbanService;
use Intern\Model\Task;
use Intern\Model\TaskStatus;
use Intern\Model\TaskPriority;
use Admin\Model\User;

class Kanban extends Controller
{
    /**
     * @var KanbanService
     */
    protected $kanbanService;

    public function initialize()
    {
        parent::initialize();
        $this->kanbanService = $this->getDI()->get(KanbanService::class);
    }

    /**
     * Get kanban board data
     * GET /intern/kanban/board
     * 
     * @return array|Response
     */
    public function boardAction()
    {
        try {
            $request = $this->getRequest();
            
            // Parse filters from query parameters
            $filters = [];
            if ($assignedTo = $request->get('assigned_to')) {
                $filters['assigned_to'] = (int)$assignedTo;
            }
            if ($priorityId = $request->get('priority_id')) {
                $filters['priority_id'] = (int)$priorityId;
            }
            if ($createdBy = $request->get('created_by')) {
                $filters['created_by'] = (int)$createdBy;
            }
            
            $boardData = $this->kanbanService->retrieveBoardLayout($filters);
            
            return $this->jsonResponse($boardData);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to load board data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move task between statuses
     * PUT /intern/kanban/task/{id}/move
     * 
     * @return Response
     */
    public function moveTaskAction()
    {
        try {
            if (!$this->validateCsrfToken()) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid CSRF token'
                ], 403);
            }
            
            $request = $this->getRequest();
            $taskId = (int)$this->getDispatcher()->getParam('id');
            
            if (!$taskId) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Task ID is required'
                ], 400);
            }
            
            $rawBody = $request->getRawBody();
            $data = json_decode($rawBody, true);
            
            if (!$data || !isset($data['status_id'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Status ID is required'
                ], 400);
            }
            
            $statusId = (int)$data['status_id'];
            $position = isset($data['position']) ? (int)$data['position'] : null;
            
            $result = $this->kanbanService->moveTask($taskId, $statusId, $position);
            
            if ($result['success']) {
                return $this->jsonResponse($result);
            } else {
                return $this->jsonResponse($result, 400);
            }
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to move task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed task information
     * GET /intern/kanban/task/{id}/details
     * 
     * @return Response
     */
    public function taskDetailsAction()
    {
        try {
            $taskId = (int)$this->getDispatcher()->getParam('id');
            
            if (!$taskId) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Task ID is required'
                ], 400);
            }
            
            $taskDetails = $this->kanbanService->getTaskDetails($taskId);
            
            if (!$taskDetails) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Task not found'
                ], 404);
            }
            
            $comments = []; // TODO: Implement comment loading
            $logs = $this->kanbanService->getRecentTaskLogs($taskId, 10);
            
            return $this->jsonResponse([
                'success' => true,
                'task' => $taskDetails,
                'comments' => $comments,
                'logs' => $logs
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to load task details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new task
     * POST /intern/kanban/task/create
     * 
     * @return Response
     */
    public function createTaskAction()
    {
        try {
            if (!$this->validateCsrfToken()) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid CSRF token'
                ], 403);
            }
            
            $request = $this->getRequest();
            $rawBody = $request->getRawBody();
            $data = json_decode($rawBody, true);
            
            if (!$data) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }
            
            // Validate required fields
            $errors = [];
            if (empty($data['title'])) {
                $errors[] = 'Title is required';
            }
            if (empty($data['status_id'])) {
                $errors[] = 'Status is required';
            }
            if (empty($data['assigned_to'])) {
                $errors[] = 'Assignee is required';
            }
            
            if (!empty($errors)) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $errors
                ], 400);
            }
            
            // Get current user as creator
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'User not authenticated'
                ], 401);
            }
            
            // Calculate position for new task
            $position = $this->kanbanService->calculateTaskPosition((int)$data['status_id']);
            
            $taskData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'status_id' => (int)$data['status_id'],
                'priority_id' => (int)($data['priority_id'] ?? 1),
                'assigned_to' => (int)$data['assigned_to'],
                'created_by' => $currentUser->id,
                'position' => $position,
                'begin_date' => $data['begin_date'] ?? null,
                'end_date' => $data['end_date'] ?? null
            ];
            
            $task = Task::create($taskData);
            
            if ($task) {
                // Get task details for response
                $taskDetails = $this->kanbanService->getTaskDetails($task->id);
                
                // Broadcast task creation event
                $this->fireEvent('kanban.taskCreated', [
                    'task_id' => $task->id,
                    'created_by' => $currentUser->id,
                    'assigned_to' => $taskData['assigned_to']
                ]);
                
                return $this->jsonResponse([
                    'success' => true,
                    'task' => $taskDetails
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Failed to create task'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to create task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update task properties
     * PUT /intern/kanban/task/{id}/update
     * 
     * @return Response
     */
    public function updateTaskAction()
    {
        try {
            if (!$this->validateCsrfToken()) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid CSRF token'
                ], 403);
            }
            
            $taskId = (int)$this->getDispatcher()->getParam('id');
            
            if (!$taskId) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Task ID is required'
                ], 400);
            }
            
            $task = Task::find($taskId);
            if (!$task) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Task not found'
                ], 404);
            }
            
            $request = $this->getRequest();
            $rawBody = $request->getRawBody();
            $data = json_decode($rawBody, true);
            
            if (!$data) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }
            
            $errors = [];
            $updatedFields = [];
            
            // Update allowed fields
            if (isset($data['title'])) {
                if (empty($data['title'])) {
                    $errors[] = 'Title cannot be empty';
                } else {
                    $task->title = $data['title'];
                    $updatedFields[] = 'title';
                }
            }
            
            if (isset($data['description'])) {
                $task->description = $data['description'];
                $updatedFields[] = 'description';
            }
            
            if (isset($data['priority_id'])) {
                $task->priority_id = (int)$data['priority_id'];
                $updatedFields[] = 'priority_id';
            }
            
            if (isset($data['assigned_to'])) {
                $task->assigned_to = (int)$data['assigned_to'];
                $updatedFields[] = 'assigned_to';
            }
            
            if (isset($data['begin_date'])) {
                $task->begin_date = $data['begin_date'];
                $updatedFields[] = 'begin_date';
            }
            
            if (isset($data['end_date'])) {
                $task->end_date = $data['end_date'];
                $updatedFields[] = 'end_date';
            }
            
            if (!empty($errors)) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $errors
                ], 400);
            }
            
            if (empty($updatedFields)) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'No valid fields to update'
                ], 400);
            }
            
            $result = $task->save();
            
            if ($result) {
                // Get updated task details
                $taskDetails = $this->kanbanService->getTaskDetails($taskId);
                
                // Fire update event
                $this->fireEvent('kanban.taskUpdated', [
                    'task_id' => $taskId,
                    'updated_fields' => $updatedFields,
                    'updated_by' => $this->getCurrentUser()->id ?? null
                ]);
                
                return $this->jsonResponse([
                    'success' => true,
                    'task' => $taskDetails
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Failed to update task'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to update task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Render kanban board view
     * GET /intern/kanban
     * 
     * @return string
     */
    public function indexAction()
    {
        try {
            // Get initial data for the view
            $statuses = TaskStatus::getKanbanStatuses();
            $priorities = TaskPriority::all();
            $users = User::select(['id', 'name'])->get();
            
            return $this->render('kanban/board', [
                'statuses' => $statuses,
                'priorities' => $priorities,
                'users' => $users
            ]);
            
        } catch (\Exception $e) {
            return $this->render('error/500', [
                'message' => 'Failed to load kanban board: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Helper method to return JSON response
     * 
     * @param array $data
     * @param int $statusCode
     * @return Response
     */
    protected function jsonResponse(array $data, int $statusCode = 200): Response
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        return $response;
    }

    /**
     * Get current authenticated user
     * 
     * @return User|null
     */
    protected function getCurrentUser(): ?User
    {
        // This should be implemented based on your authentication system
        $session = $this->getSession();
        $userId = $session->get('user_id');
        
        if ($userId) {
            return User::find($userId);
        }
        
        return null;
    }
}