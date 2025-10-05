<?php
// tests/Integration/Intern/KanbanApiTest.php
use PHPUnit\Framework\TestCase;
use Core\Mvc\Application;
use Core\Http\Request;
use Core\Http\Response;
use Core\Di\Container;
use Intern\Model\Task;
use Intern\Model\TaskStatus;
use Intern\Model\TaskPriority;
use Admin\Model\User;

class KanbanApiTest extends TestCase
{
    private $app;
    private $container;
    private $testUserId;
    private $testTaskId;

    protected function setUp(): void
    {
        // Initialize test environment
        $this->container = new Container();
        $this->app = new Application();
        $this->app->setDI($this->container);
        
        // Set up test database or mock data
        $this->setUpTestData();
        
        // Mock authentication for tests
        $this->mockAuthentication();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanUpTestData();
    }

    public function testGetBoardDataReturnsCorrectStructure()
    {
        $response = $this->makeRequest('GET', '/kanban/board');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('statuses', $data);
        $this->assertArrayHasKey('tasks', $data);
        $this->assertArrayHasKey('users', $data);
        
        // Verify statuses structure
        $this->assertIsArray($data['statuses']);
        foreach ($data['statuses'] as $status) {
            $this->assertArrayHasKey('id', $status);
            $this->assertArrayHasKey('title', $status);
            $this->assertArrayHasKey('code', $status);
            $this->assertArrayHasKey('color', $status);
            $this->assertArrayHasKey('position', $status);
            $this->assertArrayHasKey('task_count', $status);
        }
        
        // Verify tasks structure
        $this->assertIsArray($data['tasks']);
        foreach ($data['tasks'] as $statusId => $tasks) {
            $this->assertIsNumeric($statusId);
            $this->assertIsArray($tasks);
            
            foreach ($tasks as $task) {
                $this->assertArrayHasKey('id', $task);
                $this->assertArrayHasKey('title', $task);
                $this->assertArrayHasKey('priority', $task);
                $this->assertArrayHasKey('position', $task);
            }
        }
    }

    public function testGetBoardDataWithFilters()
    {
        $response = $this->makeRequest('GET', '/kanban/board?assigned_to=' . $this->testUserId);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        // Verify filtered results only contain tasks assigned to test user
        foreach ($data['tasks'] as $statusId => $tasks) {
            foreach ($tasks as $task) {
                if ($task['assigned']) {
                    $this->assertEquals($this->testUserId, $task['assigned']['id']);
                }
            }
        }
    }

    public function testMoveTaskSuccess()
    {
        $moveData = [
            'status_id' => 2, // Move to "In Progress"
            'position' => 1,
            '_token' => $this->getCsrfToken()
        ];
        
        $response = $this->makeRequest('PUT', '/kanban/task/' . $this->testTaskId . '/move', $moveData);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('task', $data);
        $this->assertArrayHasKey('logs', $data);
        
        // Verify task was actually moved
        $task = Task::find($this->testTaskId);
        $this->assertEquals(2, $task->status_id);
        $this->assertEquals(1, $task->position);
    }

    public function testMoveTaskInvalidTransition()
    {
        // Try to move a completed task to a status that doesn't allow this transition
        $completedTask = $this->createTestTask(3); // Status 3 = Completed
        
        $moveData = [
            'status_id' => 4, // Try to move to "On Hold" (invalid from Completed)
            'position' => 1,
            '_token' => $this->getCsrfToken()
        ];
        
        $response = $this->makeRequest('PUT', '/kanban/task/' . $completedTask->id . '/move', $moveData);
        
        $this->assertEquals(400, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Invalid status transition', $data['error']);
    }

    public function testMoveTaskWithoutCsrfToken()
    {
        $moveData = [
            'status_id' => 2,
            'position' => 1
            // Missing CSRF token
        ];
        
        $response = $this->makeRequest('PUT', '/kanban/task/' . $this->testTaskId . '/move', $moveData);
        
        $this->assertEquals(403, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Invalid CSRF token', $data['error']);
    }

    public function testCreateTaskSuccess()
    {
        $taskData = [
            'title' => 'New Test Task',
            'description' => 'Test description',
            'status_id' => 1,
            'priority_id' => 2,
            'assigned_to' => $this->testUserId,
            'begin_date' => '2025-10-10',
            'end_date' => '2025-10-20',
            '_token' => $this->getCsrfToken()
        ];
        
        $response = $this->makeRequest('POST', '/kanban/task/create', $taskData);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('task', $data);
        
        // Verify task was created in database
        $task = Task::find($data['task']['id']);
        $this->assertNotNull($task);
        $this->assertEquals('New Test Task', $task->title);
        $this->assertEquals($this->testUserId, $task->assigned_to);
    }

    public function testCreateTaskMissingRequiredFields()
    {
        $taskData = [
            'description' => 'Test description',
            // Missing required fields: title, status_id, assigned_to
            '_token' => $this->getCsrfToken()
        ];
        
        $response = $this->makeRequest('POST', '/kanban/task/create', $taskData);
        
        $this->assertEquals(400, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Title is required', $data['errors']);
    }

    public function testGetTaskDetails()
    {
        $response = $this->makeRequest('GET', '/kanban/task/' . $this->testTaskId . '/details');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('task', $data);
        $this->assertArrayHasKey('comments', $data);
        $this->assertArrayHasKey('logs', $data);
        
        // Verify task details structure
        $task = $data['task'];
        $this->assertEquals($this->testTaskId, $task['id']);
        $this->assertArrayHasKey('title', $task);
        $this->assertArrayHasKey('status', $task);
        $this->assertArrayHasKey('priority', $task);
        $this->assertArrayHasKey('creator', $task);
        $this->assertArrayHasKey('assigned', $task);
    }

    public function testGetTaskDetailsNotFound()
    {
        $response = $this->makeRequest('GET', '/kanban/task/99999/details');
        
        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertEquals('Task not found', $data['error']);
    }

    public function testUpdateTaskSuccess()
    {
        $updateData = [
            'title' => 'Updated Task Title',
            'description' => 'Updated description',
            'priority_id' => 3,
            '_token' => $this->getCsrfToken()
        ];
        
        $response = $this->makeRequest('PUT', '/kanban/task/' . $this->testTaskId . '/update', $updateData);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('task', $data);
        
        // Verify task was updated
        $task = Task::find($this->testTaskId);
        $this->assertEquals('Updated Task Title', $task->title);
        $this->assertEquals('Updated description', $task->description);
        $this->assertEquals(3, $task->priority_id);
    }

    public function testUpdateTaskEmptyTitle()
    {
        $updateData = [
            'title' => '', // Empty title should fail
            '_token' => $this->getCsrfToken()
        ];
        
        $response = $this->makeRequest('PUT', '/kanban/task/' . $this->testTaskId . '/update', $updateData);
        
        $this->assertEquals(400, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Title cannot be empty', $data['errors']);
    }

    public function testRenderKanbanBoardView()
    {
        $response = $this->makeRequest('GET', '/kanban');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $content = $response->getContent();
        
        // Verify HTML contains expected elements
        $this->assertStringContainsString('kanban-board', $content);
        $this->assertStringContainsString('kanban.js', $content);
        $this->assertStringContainsString('KanbanBoard', $content);
    }

    public function testApiRequestsRequireAuthentication()
    {
        // Remove authentication
        $this->mockUnauthenticated();
        
        $response = $this->makeRequest('GET', '/kanban/board');
        
        // Should redirect to login or return 401
        $this->assertContains($response->getStatusCode(), [401, 302]);
    }

    public function testConcurrentTaskMovement()
    {
        // Simulate two users trying to move tasks at the same time
        $moveData1 = [
            'status_id' => 2,
            'position' => 1,
            '_token' => $this->getCsrfToken()
        ];
        
        $moveData2 = [
            'status_id' => 2,
            'position' => 1, // Same position
            '_token' => $this->getCsrfToken()
        ];
        
        // Create second test task
        $secondTask = $this->createTestTask(1);
        
        // Move both tasks to same position simultaneously
        $response1 = $this->makeRequest('PUT', '/kanban/task/' . $this->testTaskId . '/move', $moveData1);
        $response2 = $this->makeRequest('PUT', '/kanban/task/' . $secondTask->id . '/move', $moveData2);
        
        // Both should succeed
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
        
        // Verify positions were adjusted to avoid conflicts
        $task1 = Task::find($this->testTaskId);
        $task2 = Task::find($secondTask->id);
        
        $this->assertNotEquals($task1->position, $task2->position);
    }

    public function testTaskPositionRecalculation()
    {
        // Create multiple tasks in the same status
        $tasks = [];
        for ($i = 0; $i < 5; $i++) {
            $tasks[] = $this->createTestTask(1, $i);
        }
        
        // Move middle task to different status
        $moveData = [
            'status_id' => 2,
            'position' => 1,
            '_token' => $this->getCsrfToken()
        ];
        
        $response = $this->makeRequest('PUT', '/kanban/task/' . $tasks[2]->id . '/move', $moveData);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // Verify remaining tasks in original status have correct positions
        $remainingTasks = Task::where('status_id', 1)->orderBy('position')->get();
        
        for ($i = 0; $i < count($remainingTasks); $i++) {
            $this->assertEquals($i, $remainingTasks[$i]->position);
        }
    }

    // Helper methods

    private function setUpTestData()
    {
        // Create test user
        $this->testUserId = $this->createTestUser();
        
        // Create test task
        $this->testTaskId = $this->createTestTask()->id;
        
        // Ensure task statuses exist
        $this->ensureTaskStatusesExist();
        
        // Ensure task priorities exist
        $this->ensureTaskPrioritiesExist();
    }

    private function cleanUpTestData()
    {
        // Clean up test tasks
        Task::where('created_by', $this->testUserId)->delete();
        
        // Clean up test user (if needed)
        User::find($this->testUserId)?->delete();
    }

    private function createTestUser()
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('test123', PASSWORD_DEFAULT)
        ])->id;
    }

    private function createTestTask($statusId = 1, $position = 0)
    {
        return Task::create([
            'title' => 'Test Task ' . uniqid(),
            'description' => 'Test description',
            'status_id' => $statusId,
            'priority_id' => 1,
            'created_by' => $this->testUserId,
            'assigned_to' => $this->testUserId,
            'position' => $position,
            'begin_date' => '2025-10-01',
            'end_date' => '2025-10-15'
        ]);
    }

    private function ensureTaskStatusesExist()
    {
        $statuses = [
            ['id' => 1, 'title' => 'New', 'code' => 'new', 'color' => 'light', 'position' => 0, 'is_active' => true],
            ['id' => 2, 'title' => 'In Progress', 'code' => 'progress', 'color' => 'primary', 'position' => 1, 'is_active' => true],
            ['id' => 3, 'title' => 'Completed', 'code' => 'completed', 'color' => 'success', 'position' => 2, 'is_active' => true],
            ['id' => 4, 'title' => 'On Hold', 'code' => 'hold', 'color' => 'secondary', 'position' => 3, 'is_active' => true],
        ];

        foreach ($statuses as $status) {
            TaskStatus::updateOrCreate(['id' => $status['id']], $status);
        }
    }

    private function ensureTaskPrioritiesExist()
    {
        $priorities = [
            ['id' => 1, 'title' => 'Low', 'code' => 'low', 'color' => 'light'],
            ['id' => 2, 'title' => 'Medium', 'code' => 'medium', 'color' => 'secondary'],
            ['id' => 3, 'title' => 'High', 'code' => 'high', 'color' => 'warning'],
            ['id' => 4, 'title' => 'Critical', 'code' => 'critical', 'color' => 'danger'],
        ];

        foreach ($priorities as $priority) {
            TaskPriority::updateOrCreate(['id' => $priority['id']], $priority);
        }
    }

    private function mockAuthentication()
    {
        // Mock session with authenticated user
        $mockSession = $this->createMock(\Core\Session\SessionInterface::class);
        $mockSession->method('get')->with('user_id')->willReturn($this->testUserId);
        $mockSession->method('get')->with('csrf_token')->willReturn('test_csrf_token');
        
        $this->container->set('session', $mockSession);
    }

    private function mockUnauthenticated()
    {
        $mockSession = $this->createMock(\Core\Session\SessionInterface::class);
        $mockSession->method('get')->with('user_id')->willReturn(null);
        
        $this->container->set('session', $mockSession);
    }

    private function getCsrfToken()
    {
        return 'test_csrf_token';
    }

    private function makeRequest($method, $path, $data = [])
    {
        // Create mock request
        $request = $this->createMock(Request::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getURI')->willReturn($path);
        
        if (!empty($data)) {
            $request->method('getRawBody')->willReturn(json_encode($data));
            if ($method === 'POST') {
                $request->method('post')->willReturnCallback(function($key) use ($data) {
                    return $data[$key] ?? null;
                });
            }
        }
        
        // Set request in container
        $this->container->set('request', $request);
        
        // Create response
        $response = new Response();
        $this->container->set('response', $response);
        
        // Dispatch request through application
        return $this->app->handle($request);
    }
}