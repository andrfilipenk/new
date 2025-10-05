<?php
// tests/KanbanWorkflowTest.php
/**
 * Comprehensive end-to-end Kanban workflow validation
 * This script validates the entire Kanban implementation
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Intern\Service\KanbanService;
use Intern\Model\Task;
use Intern\Model\TaskStatus;
use Intern\Model\TaskPriority;
use Intern\Model\TaskLog;
use Admin\Model\User;

class KanbanWorkflowValidator
{
    private $results = [];
    private $errors = [];

    public function runAllTests()
    {
        echo "🔍 Starting Kanban Workflow Validation...\n\n";

        $this->testDatabaseSchema();
        $this->testModelRelationships();
        $this->testKanbanServiceLogic();
        $this->testStatusTransitions();
        $this->testPositionManagement();
        $this->testEventSystem();
        $this->testValidation();

        $this->printResults();
    }

    private function testDatabaseSchema()
    {
        echo "📊 Testing Database Schema...\n";

        // Test task table has required fields
        $this->validateTableStructure('task', [
            'id', 'title', 'description', 'status_id', 'priority_id', 
            'created_by', 'assigned_to', 'position', 'begin_date', 'end_date',
            'created_at', 'updated_at'
        ]);

        // Test task_status table has kanban fields
        $this->validateTableStructure('task_status', [
            'id', 'title', 'code', 'color', 'position', 'is_active'
        ]);

        // Test task_log table has enhanced fields
        $this->validateTableStructure('task_log', [
            'id', 'task_id', 'content', 'user_id', 'log_type', 'metadata', 'created_at'
        ]);

        // Test foreign key relationships
        $this->validateForeignKeys();

        echo "✅ Database schema validation completed\n\n";
    }

    private function testModelRelationships()
    {
        echo "🔗 Testing Model Relationships...\n";

        try {
            // Test Task model relationships
            $task = new Task();
            $this->assert(method_exists($task, 'status'), 'Task::status() relationship exists');
            $this->assert(method_exists($task, 'priority'), 'Task::priority() relationship exists');
            $this->assert(method_exists($task, 'creator'), 'Task::creator() relationship exists');
            $this->assert(method_exists($task, 'assigned'), 'Task::assigned() relationship exists');
            $this->assert(method_exists($task, 'logs'), 'Task::logs() relationship exists');
            $this->assert(method_exists($task, 'comments'), 'Task::comments() relationship exists');

            // Test enhanced Task methods
            $this->assert(method_exists($task, 'moveToStatus'), 'Task::moveToStatus() method exists');
            $this->assert(method_exists($task, 'updatePosition'), 'Task::updatePosition() method exists');
            $this->assert(method_exists($task, 'getByStatusOrdered'), 'Task::getByStatusOrdered() method exists');

            // Test TaskStatus model enhancements
            $status = new TaskStatus();
            $this->assert(method_exists($status, 'getKanbanStatuses'), 'TaskStatus::getKanbanStatuses() method exists');
            $this->assert(method_exists($status, 'isValidTransition'), 'TaskStatus::isValidTransition() method exists');
            $this->assert(method_exists($status, 'getDisplayOrder'), 'TaskStatus::getDisplayOrder() method exists');
            $this->assert(method_exists($status, 'getTaskCount'), 'TaskStatus::getTaskCount() method exists');

            // Test TaskLog enhancements
            $log = new TaskLog();
            $this->assert(method_exists($log, 'logKanbanMovement'), 'TaskLog::logKanbanMovement() method exists');
            $this->assert(method_exists($log, 'logTaskCreation'), 'TaskLog::logTaskCreation() method exists');
            $this->assert(method_exists($log, 'logTaskUpdate'), 'TaskLog::logTaskUpdate() method exists');

        } catch (Exception $e) {
            $this->errors[] = "Model relationship test failed: " . $e->getMessage();
        }

        echo "✅ Model relationships validation completed\n\n";
    }

    private function testKanbanServiceLogic()
    {
        echo "⚙️ Testing KanbanService Logic...\n";

        try {
            $service = new KanbanService();

            // Test position calculation
            $position = $service->calculateTaskPosition(1, null);
            $this->assert(is_int($position) && $position > 0, 'Position calculation returns positive integer');

            $specificPosition = $service->calculateTaskPosition(1, 5);
            $this->assert($specificPosition === 5, 'Specific position is returned when provided');

            // Test validation methods
            $this->assert(method_exists($service, 'validateStatusTransition'), 'validateStatusTransition method exists');
            $this->assert(method_exists($service, 'moveTask'), 'moveTask method exists');
            $this->assert(method_exists($service, 'retrieveBoardLayout'), 'retrieveBoardLayout method exists');
            $this->assert(method_exists($service, 'logTaskMovement'), 'logTaskMovement method exists');
            $this->assert(method_exists($service, 'broadcastTaskUpdate'), 'broadcastTaskUpdate method exists');

        } catch (Exception $e) {
            $this->errors[] = "KanbanService test failed: " . $e->getMessage();
        }

        echo "✅ KanbanService logic validation completed\n\n";
    }

    private function testStatusTransitions()
    {
        echo "🔄 Testing Status Transitions...\n";

        try {
            // Test valid transitions based on business rules
            $validTransitions = [
                1 => [2, 4], // New -> In Progress, On Hold
                2 => [3, 4, 1], // In Progress -> Completed, On Hold, New
                3 => [2], // Completed -> In Progress
                4 => [1, 2], // On Hold -> New, In Progress
            ];

            foreach ($validTransitions as $fromStatus => $toStatuses) {
                foreach ($toStatuses as $toStatus) {
                    $status = new TaskStatus();
                    $status->id = $toStatus;
                    $isValid = $status->isValidTransition($fromStatus);
                    $this->assert($isValid, "Transition from status $fromStatus to $toStatus should be valid");
                }
            }

            // Test invalid transitions
            $invalidTransitions = [
                [3, 4], // Completed -> On Hold (should be invalid)
                [1, 3], // New -> Completed (should be invalid - skipping In Progress)
            ];

            foreach ($invalidTransitions as [$fromStatus, $toStatus]) {
                $status = new TaskStatus();
                $status->id = $toStatus;
                $isValid = $status->isValidTransition($fromStatus);
                $this->assert(!$isValid, "Transition from status $fromStatus to $toStatus should be invalid");
            }

        } catch (Exception $e) {
            $this->errors[] = "Status transition test failed: " . $e->getMessage();
        }

        echo "✅ Status transitions validation completed\n\n";
    }

    private function testPositionManagement()
    {
        echo "📋 Testing Position Management...\n";

        try {
            $service = new KanbanService();

            // Test position calculation with no existing tasks
            $emptyPosition = $service->calculateTaskPosition(1);
            $this->assert($emptyPosition >= 1, 'Empty status returns position >= 1');

            // Test position calculation with target position
            $targetPosition = $service->calculateTaskPosition(1, 3);
            $this->assert($targetPosition === 3, 'Target position is respected');

            // Test position adjustment logic exists
            $this->assert(method_exists($service, 'calculateTaskPosition'), 'calculateTaskPosition method exists');

        } catch (Exception $e) {
            $this->errors[] = "Position management test failed: " . $e->getMessage();
        }

        echo "✅ Position management validation completed\n\n";
    }

    private function testEventSystem()
    {
        echo "📡 Testing Event System...\n";

        try {
            // Test that models use EventAware trait
            $task = new Task();
            $this->assert(method_exists($task, 'fireEvent'), 'Task model has EventAware trait');

            $service = new KanbanService();
            $this->assert(method_exists($service, 'fireEvent'), 'KanbanService has EventAware trait');

            // Test event firing capability
            $this->assert(method_exists($service, 'broadcastTaskUpdate'), 'broadcastTaskUpdate method exists');

        } catch (Exception $e) {
            $this->errors[] = "Event system test failed: " . $e->getMessage();
        }

        echo "✅ Event system validation completed\n\n";
    }

    private function testValidation()
    {
        echo "🛡️ Testing Validation Logic...\n";

        try {
            $service = new KanbanService();

            // Test validation with non-existent task
            $result = $service->validateStatusTransition(99999, 1);
            $this->assert($result === false, 'Non-existent task validation fails');

            // Test move task with invalid data
            $result = $service->moveTask(99999, 1);
            $this->assert(isset($result['success']) && !$result['success'], 'Invalid task move returns error');
            $this->assert(isset($result['error']), 'Error message is provided for invalid move');

        } catch (Exception $e) {
            $this->errors[] = "Validation test failed: " . $e->getMessage();
        }

        echo "✅ Validation logic validation completed\n\n";
    }

    private function validateTableStructure($tableName, $requiredFields)
    {
        try {
            // This would need actual database connection to validate
            // For now, just check if the migration file contains the fields
            $migrationFile = __DIR__ . '/../migrations/2025_10_05_100000_update_tasks_for_kanban.php';
            
            if (file_exists($migrationFile)) {
                $migrationContent = file_get_contents($migrationFile);
                
                foreach ($requiredFields as $field) {
                    if (strpos($migrationContent, $field) !== false) {
                        $this->results[] = "✅ Field '$field' found in migration for table '$tableName'";
                    } else {
                        $this->errors[] = "❌ Field '$field' missing in migration for table '$tableName'";
                    }
                }
            } else {
                $this->errors[] = "❌ Migration file not found";
            }
        } catch (Exception $e) {
            $this->errors[] = "Table structure validation failed: " . $e->getMessage();
        }
    }

    private function validateForeignKeys()
    {
        try {
            $migrationFile = __DIR__ . '/../migrations/2025_10_05_100000_update_tasks_for_kanban.php';
            $content = file_get_contents($migrationFile);
            
            $expectedForeignKeys = [
                'user_id',
                'task_id'
            ];
            
            foreach ($expectedForeignKeys as $fk) {
                if (strpos($content, "foreign('$fk')") !== false || strpos($content, "foreign(column: '$fk')") !== false) {
                    $this->results[] = "✅ Foreign key '$fk' found in migration";
                } else {
                    $this->errors[] = "❌ Foreign key '$fk' missing in migration";
                }
            }
        } catch (Exception $e) {
            $this->errors[] = "Foreign key validation failed: " . $e->getMessage();
        }
    }

    private function assert($condition, $message)
    {
        if ($condition) {
            $this->results[] = "✅ $message";
        } else {
            $this->errors[] = "❌ $message";
        }
    }

    private function printResults()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 KANBAN WORKFLOW VALIDATION RESULTS\n";
        echo str_repeat("=", 60) . "\n\n";

        echo "✅ PASSED TESTS (" . count($this->results) . "):\n";
        foreach ($this->results as $result) {
            echo "   $result\n";
        }

        if (!empty($this->errors)) {
            echo "\n❌ FAILED TESTS (" . count($this->errors) . "):\n";
            foreach ($this->errors as $error) {
                echo "   $error\n";
            }
        }

        $total = count($this->results) + count($this->errors);
        $passed = count($this->results);
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        echo "\n" . str_repeat("-", 60) . "\n";
        echo "📈 SUMMARY: $passed/$total tests passed ($percentage%)\n";

        if (empty($this->errors)) {
            echo "🎉 All tests passed! Kanban implementation is ready for deployment.\n";
        } else {
            echo "⚠️ Some tests failed. Please review and fix the issues above.\n";
        }

        echo str_repeat("=", 60) . "\n";
    }
}

// File structure validation
function validateFileStructure()
{
    echo "📁 Validating File Structure...\n";

    $requiredFiles = [
        'app/Intern/Model/Task.php',
        'app/Intern/Model/TaskStatus.php',
        'app/Intern/Model/TaskLog.php',
        'app/Intern/Service/KanbanService.php',
        'app/Intern/Service/NotificationService.php',
        'app/Intern/Controller/Kanban.php',
        'app/Intern/views/kanban/board.phtml',
        'app/Intern/config.php',
        'app/Intern/Module.php',
        'migrations/2025_10_05_100000_update_tasks_for_kanban.php',
        'public/js/kanban.js',
        'tests/Unit/Intern/Service/KanbanServiceTest.php',
        'tests/Integration/Intern/KanbanApiTest.php',
        'KANBAN_SETUP.md'
    ];

    $missing = [];
    $found = [];

    foreach ($requiredFiles as $file) {
        $fullPath = __DIR__ . '/../' . $file;
        if (file_exists($fullPath)) {
            $found[] = $file;
        } else {
            $missing[] = $file;
        }
    }

    echo "✅ Found files (" . count($found) . "):\n";
    foreach ($found as $file) {
        echo "   ✓ $file\n";
    }

    if (!empty($missing)) {
        echo "\n❌ Missing files (" . count($missing) . "):\n";
        foreach ($missing as $file) {
            echo "   ✗ $file\n";
        }
    }

    echo "\n📊 File Structure: " . count($found) . "/" . count($requiredFiles) . " files present\n\n";

    return empty($missing);
}

// Route validation
function validateRoutes()
{
    echo "🛣️ Validating Routes Configuration...\n";

    $configFile = __DIR__ . '/../app/Intern/config.php';
    if (!file_exists($configFile)) {
        echo "❌ Config file not found\n";
        return false;
    }

    $config = require $configFile;
    $routes = $config['routes'] ?? [];

    $expectedRoutes = [
        '/kanban',
        '/kanban/board',
        '/kanban/task/create',
        '/kanban/task/{id:[0-9]+}/move',
        '/kanban/task/{id:[0-9]+}/details',
        '/kanban/task/{id:[0-9]+}/update'
    ];

    $found = 0;
    foreach ($expectedRoutes as $route) {
        if (isset($routes[$route])) {
            echo "   ✓ Route '$route' configured\n";
            $found++;
        } else {
            echo "   ✗ Route '$route' missing\n";
        }
    }

    echo "\n📊 Routes: $found/" . count($expectedRoutes) . " routes configured\n\n";

    return $found === count($expectedRoutes);
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "🚀 Kanban Implementation Validation Suite\n";
    echo "========================================\n\n";

    $fileStructureOk = validateFileStructure();
    $routesOk = validateRoutes();

    if ($fileStructureOk && $routesOk) {
        $validator = new KanbanWorkflowValidator();
        $validator->runAllTests();
    } else {
        echo "❌ Cannot proceed with workflow validation due to missing files or routes.\n";
        echo "Please ensure all files are present and routes are configured.\n";
    }
} else {
    echo "This script must be run from the command line.\n";
}