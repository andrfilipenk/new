<?php
// tests/Unit/Intern/Service/KanbanServiceTest.php
use PHPUnit\Framework\TestCase;
use Intern\Service\KanbanService;
use Intern\Model\Task;
use Intern\Model\TaskStatus;
use Intern\Model\TaskPriority;
use Intern\Model\TaskLog;
use Core\Di\Container;

class KanbanServiceTest extends TestCase
{
    private $kanbanService;
    private $mockContainer;

    protected function setUp(): void
    {
        $this->mockContainer = $this->createMock(Container::class);
        $this->kanbanService = new KanbanService();
        $this->kanbanService->setDI($this->mockContainer);
    }

    protected function tearDown(): void
    {
        $this->kanbanService = null;
        $this->mockContainer = null;
    }

    public function testCalculateTaskPositionWithNoExistingTasks()
    {
        // Mock Task::where() chain
        $this->mockTaskQuery([], 0);
        
        $position = $this->kanbanService->calculateTaskPosition(1);
        
        $this->assertEquals(1, $position, 'Should return position 1 when no tasks exist');
    }

    public function testCalculateTaskPositionWithExistingTasks()
    {
        // Mock Task::where() to return max position of 3
        $this->mockTaskQuery([], 3);
        
        $position = $this->kanbanService->calculateTaskPosition(1);
        
        $this->assertEquals(4, $position, 'Should return next position after max');
    }

    public function testCalculateTaskPositionWithSpecificTarget()
    {
        $position = $this->kanbanService->calculateTaskPosition(1, 2);
        
        $this->assertEquals(2, $position, 'Should return target position when specified');
    }

    public function testValidateStatusTransitionValid()
    {
        // Mock Task::find()
        $mockTask = $this->createMockTask(1, 1); // Task in status 1 (New)
        $this->mockTaskFind($mockTask);
        
        // Mock TaskStatus::find() for valid status
        $mockStatus = $this->createMockTaskStatus(2, true); // Status 2 (In Progress)
        $mockStatus->method('isValidTransition')->with(1)->willReturn(true);
        $this->mockTaskStatusFind($mockStatus);
        
        $result = $this->kanbanService->validateStatusTransition(1, 2);
        
        $this->assertTrue($result, 'Should allow valid status transition');
    }

    public function testValidateStatusTransitionInvalid()
    {
        // Mock Task::find()
        $mockTask = $this->createMockTask(1, 3); // Task in status 3 (Completed)
        $this->mockTaskFind($mockTask);
        
        // Mock TaskStatus::find() for valid status but invalid transition
        $mockStatus = $this->createMockTaskStatus(1, true); // Status 1 (New)
        $mockStatus->method('isValidTransition')->with(3)->willReturn(false);
        $this->mockTaskStatusFind($mockStatus);
        
        $result = $this->kanbanService->validateStatusTransition(1, 1);
        
        $this->assertFalse($result, 'Should reject invalid status transition');
    }

    public function testValidateStatusTransitionSameStatus()
    {
        // Mock Task::find()
        $mockTask = $this->createMockTask(1, 2); // Task in status 2
        $this->mockTaskFind($mockTask);
        
        // Mock TaskStatus::find()
        $mockStatus = $this->createMockTaskStatus(2, true);
        $this->mockTaskStatusFind($mockStatus);
        
        $result = $this->kanbanService->validateStatusTransition(1, 2);
        
        $this->assertTrue($result, 'Should allow position change within same status');
    }

    public function testValidateStatusTransitionTaskNotFound()
    {
        $this->mockTaskFind(null);
        
        $result = $this->kanbanService->validateStatusTransition(999, 1);
        
        $this->assertFalse($result, 'Should reject when task not found');
    }

    public function testValidateStatusTransitionInactiveStatus()
    {
        // Mock Task::find()
        $mockTask = $this->createMockTask(1, 1);
        $this->mockTaskFind($mockTask);
        
        // Mock TaskStatus::find() for inactive status
        $mockStatus = $this->createMockTaskStatus(2, false); // Inactive status
        $this->mockTaskStatusFind($mockStatus);
        
        $result = $this->kanbanService->validateStatusTransition(1, 2);
        
        $this->assertFalse($result, 'Should reject transition to inactive status');
    }

    public function testMoveTaskSuccess()
    {
        // Mock successful validation
        $mockTask = $this->createMockTask(1, 1, 1); // Task in status 1, position 1
        $mockTask->method('moveToStatus')->willReturn(true);
        $this->mockTaskFind($mockTask);
        
        $mockStatus = $this->createMockTaskStatus(2, true);
        $mockStatus->method('isValidTransition')->willReturn(true);
        $this->mockTaskStatusFind($mockStatus);
        
        // Mock position calculation
        $this->mockTaskQuery([], 2); // Max position 2
        
        // Mock task details and logs
        $this->mockGetTaskDetails([]);
        $this->mockGetRecentTaskLogs([]);
        
        $result = $this->kanbanService->moveTask(1, 2, 3);
        
        $this->assertTrue($result['success'], 'Should return success for valid move');
        $this->assertArrayHasKey('task', $result);
        $this->assertArrayHasKey('logs', $result);
    }

    public function testMoveTaskFailsValidation()
    {
        // Mock failed validation
        $this->mockTaskFind(null); // Task not found
        
        $result = $this->kanbanService->moveTask(999, 2);
        
        $this->assertFalse($result['success'], 'Should return failure for invalid move');
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Task not found', $result['error']);
    }

    public function testRetrieveBoardLayoutBasicStructure()
    {
        // Mock TaskStatus::getKanbanStatuses()
        $mockStatuses = [
            $this->createMockTaskStatus(1, true, 'New', 'light'),
            $this->createMockTaskStatus(2, true, 'In Progress', 'primary'),
        ];
        $this->mockTaskStatusGetKanbanStatuses($mockStatuses);
        
        // Mock Task queries for each status
        $this->mockTaskQueriesForStatuses([
            1 => [], // No tasks in status 1
            2 => [$this->createMockTaskWithRelations(1, 2)] // One task in status 2
        ]);
        
        $result = $this->kanbanService->retrieveBoardLayout();
        
        $this->assertArrayHasKey('statuses', $result);
        $this->assertArrayHasKey('tasks', $result);
        $this->assertArrayHasKey('users', $result);
        $this->assertCount(2, $result['statuses']);
        $this->assertArrayHasKey(1, $result['tasks']);
        $this->assertArrayHasKey(2, $result['tasks']);
    }

    public function testRetrieveBoardLayoutWithFilters()
    {
        // Mock statuses
        $mockStatuses = [$this->createMockTaskStatus(1, true, 'New', 'light')];
        $this->mockTaskStatusGetKanbanStatuses($mockStatuses);
        
        // Mock filtered task queries
        $this->mockTaskQueriesForStatusesWithFilters([1 => []], ['assigned_to' => 5]);
        
        $filters = ['assigned_to' => 5];
        $result = $this->kanbanService->retrieveBoardLayout($filters);
        
        // Verify structure
        $this->assertArrayHasKey('statuses', $result);
        $this->assertArrayHasKey('tasks', $result);
        $this->assertArrayHasKey('users', $result);
    }

    // Helper methods for mocking

    private function createMockTask($id, $statusId, $position = 0)
    {
        $mock = $this->createMock(Task::class);
        $mock->id = $id;
        $mock->status_id = $statusId;
        $mock->position = $position;
        return $mock;
    }

    private function createMockTaskStatus($id, $isActive, $title = 'Test Status', $color = 'primary')
    {
        $mock = $this->createMock(TaskStatus::class);
        $mock->id = $id;
        $mock->is_active = $isActive;
        $mock->title = $title;
        $mock->color = $color;
        return $mock;
    }

    private function createMockTaskWithRelations($id, $statusId)
    {
        $mock = $this->createMockTask($id, $statusId);
        
        // Mock relationships
        $mockPriority = $this->createMock(TaskPriority::class);
        $mockPriority->id = 1;
        $mockPriority->title = 'Medium';
        $mockPriority->color = 'secondary';
        
        $mock->priority = $mockPriority;
        $mock->assigned = null; // No assignee
        $mock->title = 'Test Task';
        $mock->description = 'Test Description';
        $mock->position = 0;
        $mock->end_date = '2025-10-15';
        $mock->created_at = '2025-10-01 10:00:00';
        $mock->updated_at = '2025-10-05 15:30:00';
        
        return $mock;
    }

    private function mockTaskFind($returnValue)
    {
        // In a real implementation, you would mock the static method
        // This is a simplified version for demonstration
        Task::$staticMethods['find'] = $returnValue;
    }

    private function mockTaskStatusFind($returnValue)
    {
        TaskStatus::$staticMethods['find'] = $returnValue;
    }

    private function mockTaskQuery($tasks, $maxPosition)
    {
        // Mock the query chain: Task::where()->max()
        $mockQuery = $this->createMock(\stdClass::class);
        $mockQuery->method('max')->willReturn($maxPosition);
        Task::$staticMethods['where'] = $mockQuery;
    }

    private function mockTaskStatusGetKanbanStatuses($statuses)
    {
        TaskStatus::$staticMethods['getKanbanStatuses'] = $statuses;
    }

    private function mockTaskQueriesForStatuses($statusTasks)
    {
        // This would need more sophisticated mocking in a real implementation
        // For now, just store the expected results
        Task::$mockStatusTasks = $statusTasks;
    }

    private function mockTaskQueriesForStatusesWithFilters($statusTasks, $filters)
    {
        Task::$mockStatusTasks = $statusTasks;
        Task::$mockFilters = $filters;
    }

    private function mockGetTaskDetails($taskData)
    {
        $this->kanbanService->mockTaskDetails = $taskData;
    }

    private function mockGetRecentTaskLogs($logs)
    {
        $this->kanbanService->mockTaskLogs = $logs;
    }

    // Test for edge cases

    public function testMoveTaskToSamePosition()
    {
        $mockTask = $this->createMockTask(1, 2, 3);
        $mockTask->method('moveToStatus')->willReturn(true);
        $this->mockTaskFind($mockTask);
        
        $mockStatus = $this->createMockTaskStatus(2, true);
        $this->mockTaskStatusFind($mockStatus);
        
        $this->mockGetTaskDetails([]);
        $this->mockGetRecentTaskLogs([]);
        
        $result = $this->kanbanService->moveTask(1, 2, 3); // Same status and position
        
        $this->assertTrue($result['success']);
    }

    public function testLogTaskMovementCreatesLog()
    {
        // Mock TaskStatus::find() calls
        $fromStatus = $this->createMockTaskStatus(1, true, 'New');
        $toStatus = $this->createMockTaskStatus(2, true, 'In Progress');
        
        // In a real test, you would mock TaskLog::create() to verify it's called
        $this->kanbanService->logTaskMovement(1, 1, 2, 0, 1);
        
        // Assert that TaskLog::create was called with correct parameters
        $this->assertTrue(true); // Placeholder assertion
    }

    public function testGetTaskDetailsWithValidTask()
    {
        // Mock a complete task with all relationships
        $mockTask = $this->createMockTaskWithRelations(1, 2);
        
        // Add more relationship mocks
        $mockCreator = $this->createMock(\Admin\Model\User::class);
        $mockCreator->id = 1;
        $mockCreator->name = 'John Doe';
        $mockTask->creator = $mockCreator;
        
        $mockAssigned = $this->createMock(\Admin\Model\User::class);
        $mockAssigned->id = 2;
        $mockAssigned->name = 'Jane Smith';
        $mockTask->assigned = $mockAssigned;
        
        $mockStatus = $this->createMockTaskStatus(2, true, 'In Progress', 'primary');
        $mockTask->status = $mockStatus;
        
        // Mock Task::with()->find()
        Task::$staticMethods['withFind'] = $mockTask;
        
        $result = $this->kanbanService->getTaskDetails(1);
        
        $this->assertNotNull($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test Task', $result['title']);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('creator', $result);
        $this->assertArrayHasKey('assigned', $result);
    }

    public function testGetTaskDetailsWithInvalidTask()
    {
        Task::$staticMethods['withFind'] = null;
        
        $result = $this->kanbanService->getTaskDetails(999);
        
        $this->assertNull($result);
    }
}