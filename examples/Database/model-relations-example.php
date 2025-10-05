<?php
// examples/model-relations-example.php
// Comprehensive Model Relations and Custom Filtering Examples

require_once __DIR__ . '/../app/bootstrap.php';

use Intern\Model\Task;
use Admin\Model\User;

class ModelRelationsExample
{
    public function basicEagerLoading()
    {
        echo "=== Basic Eager Loading Examples ===\n";
        
        // 1. Load single relation
        $users = User::with(['createdTasks'])->get();
        echo "Loaded " . count($users) . " users with their created tasks\n";
        
        // 2. Load multiple relations
        $users = User::with(['createdTasks', 'assignedTasks'])->get();
        echo "Loaded users with created and assigned tasks\n";
        
        // 3. Load nested relations
        $tasks = Task::with(['creator.assignedTasks'])->get();
        echo "Loaded tasks with creator and their assigned tasks\n";
    }
    
    public function constrainedEagerLoading()
    {
        echo "\n=== Constrained Eager Loading Examples ===\n";
        
        // 1. Basic constraints on relations
        $users = User::with([
            'createdTasks' => function($query) {
                $query->where('status_id', 1) // Only active tasks
                      ->orderBy('created_at', 'desc')
                      ->limit(5);
            }
        ])->get();
        echo "Loaded users with their 5 most recent active created tasks\n";
        
        // 2. Multiple constraints
        $tasks = Task::with([
            'creator' => function($query) {
                $query->where('custom_id', '>', 2000); // Users with custom_id > 2000
            }
        ])->get();
        echo "Loaded tasks with specific creators\n";
    }
    
    public function customFilteringBeforeRelations()
    {
        echo "\n=== Custom Filtering Before Relations ===\n";
        
        // 1. Filter main query before eager loading
        $users = User::with(['createdTasks', 'assignedTasks'])
            ->where('custom_id', '>', 2000)
            ->where('created_at', '>=', date('Y-m-d', strtotime('-1 year')))
            ->orderBy('name')
            ->get();
        echo "Loaded users with custom_id > 2000 from last year with their tasks\n";
        
        // 2. Using withFilters for complex main query filtering
        $tasks = Task::with(['creator', 'assigned'])
            ->withFilters(function($query) {
                $query->where('status_id', '!=', 3) // Not completed
                      ->where('priority_id', '>=', 2) // Medium priority or higher
                      ->whereRaw('end_date >= CURDATE()'); // Not overdue
            })
            ->get();
        echo "Loaded non-completed, medium+ priority, non-overdue tasks with relations\n";
    }
    
    public function advancedFilteringWithJoins()
    {
        echo "\n=== Advanced Filtering with Joins ===\n";
        
        // 1. Filter by related data using whereWithRelation
        $users = User::with([
            'createdTasks' => function($query) {
                $query->where('priority_id', 3); // High priority only
            }
        ])
        ->whereWithRelation('createdTasks', function($query) {
            $query->where('status_id', 1)  // Active tasks
                  ->where('priority_id', '>=', 2); // Medium+ priority
        })
        ->get();
        echo "Loaded users who have active medium+ priority created tasks\n";
        
        // 2. Complex filtering with multiple relations
        $tasks = Task::with(['creator', 'assigned', 'status', 'priority'])
            ->andWhere('begin_date', '<=', date('Y-m-d'))
            ->andWhere('end_date', '>=', date('Y-m-d'))
            ->whereWithRelation('creator', function($query) {
                $query->where('active', 1)
                      ->where('role', 'manager');
            })
            ->get();
        echo "Loaded current tasks created by active managers\n";
    }
    
    public function conditionalEagerLoading()
    {
        echo "\n=== Conditional Eager Loading ===\n";
        
        // Load different relations based on conditions
        $includeComments = true;
        $relations = ['creator', 'assigned', 'status', 'priority'];
        
        if ($includeComments) {
            $relations['comments'] = function($query) {
                $query->orderBy('created_at', 'desc')->limit(3);
            };
        }
        
        $tasks = Task::with($relations)
            ->where('status_id', '!=', 4) // Not cancelled
            ->get();
        echo "Conditionally loaded tasks with comments\n";
    }
    
    public function performanceOptimizedQueries()
    {
        echo "\n=== Performance Optimized Queries ===\n";
        
        // 1. Select only needed columns
        $users = User::with([
            'createdTasks' => function($query) {
                $query->select(['id', 'title', 'status_id', 'created_by'])
                      ->where('status_id', 1);
            }
        ])
        ->select(['id', 'name', 'email'])
        ->where('active', 1)
        ->get();
        echo "Loaded users with optimized column selection\n";
        
        // 2. Paginated results with relations
        // Note: You'd need to implement this with your pagination system
        $tasks = Task::with(['creator:id,name', 'status:id,name'])
            ->select(['id', 'title', 'created_by', 'status_id'])
            ->where('status_id', 1)
            ->limit(20)
            ->offset(0)
            ->get();
        echo "Loaded paginated tasks with minimal data\n";
    }
    
    public function dynamicRelationLoading()
    {
        echo "\n=== Dynamic Relation Loading ===\n";
        
        // Build relations array dynamically
        $relations = [];
        $userRole = 'admin'; // This would come from session/auth
        
        if ($userRole === 'admin') {
            $relations['createdTasks'] = function($query) {
                $query->orderBy('created_at', 'desc');
            };
            $relations['assignedTasks'] = function($query) {
                $query->where('status_id', '!=', 4); // Not cancelled
            };
        } else {
            $relations['assignedTasks'] = function($query) {
                $query->where('status_id', 1); // Only active
            };
        }
        
        $users = User::with($relations)
            ->where('active', 1)
            ->get();
        echo "Loaded users with role-based dynamic relations\n";
    }
    
    public function chainedFilteringExample()
    {
        echo "\n=== Chained Filtering Example ===\n";
        
        // Complex chained filtering
        $result = Task::with([
                'creator' => function($query) {
                    $query->where('active', 1);
                },
                'assigned' => function($query) {
                    $query->where('active', 1);
                },
                'comments' => function($query) {
                    $query->where('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
                          ->orderBy('created_at', 'desc');
                }
            ])
            ->where('status_id', 1) // Active tasks
            ->andWhere('priority_id', '>=', 2) // Medium+ priority
            ->whereRaw('begin_date <= CURDATE()') // Started
            ->whereRaw('end_date >= CURDATE()') // Not ended
            ->withFilters(function($query) {
                $query->orderBy('priority_id', 'desc')
                      ->orderBy('end_date', 'asc');
            })
            ->limit(10)
            ->get();
            
        echo "Loaded top 10 current high-priority tasks with active participants and recent comments\n";
        echo "Found " . count($result) . " tasks\n";
    }
}

// Run examples
$examples = new ModelRelationsExample();

try {
    echo "Testing model relations system...\n";
    
    // Test basic functionality first
    echo "\n=== Testing Basic Model Operations ===\n";
    $tasks = Task::limit(3)->get();
    echo "Found " . count($tasks) . " tasks\n";
    
    if (count($tasks) > 0) {
        $examples->basicEagerLoading();
        $examples->constrainedEagerLoading();
        $examples->customFilteringBeforeRelations();
        // Comment out advanced examples for now
        // $examples->advancedFilteringWithJoins();
        // $examples->conditionalEagerLoading();
        // $examples->performanceOptimizedQueries();
        // $examples->dynamicRelationLoading();
        // $examples->chainedFilteringExample();
    } else {
        echo "No test data available. Please ensure you have data in your database.\n";
    }
    
    echo "\n=== All examples completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}