<?php
/**
 * Error Handling Demonstration Script
 * 
 * This script demonstrates various error handling scenarios and improvements
 * that could be implemented in the framework.
 */

require_once __DIR__ . '/../app/bootstrap.php';

echo "=== Framework Error Handling Demonstration ===\n\n";

// Demo 1: Current Database Error Handling
echo "1. Current Database Error Handling:\n";
echo str_repeat('-', 50) . "\n";
try {
    $db = $di->get('db');
    // Simulate a database error
    $result = $db->table('non_existent_table')->where('id', 999)->first();
} catch (Exception $e) {
    echo "Current Exception Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . basename($e->getFile()) . ":" . $e->getLine() . "\n\n";
}

// Demo 2: Simulated Enhanced Database Exception
echo "2. Enhanced Database Exception (Simulation):\n";
echo str_repeat('-', 50) . "\n";
class SimulatedQueryException extends Exception 
{
    private $sql;
    private $bindings;
    private $context;
    
    public function __construct($message, $sql = '', $bindings = [], $previous = null)
    {
        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->context = ['sql' => $sql, 'bindings' => $bindings];
        parent::__construct($message, 0, $previous);
    }
    
    public function getContext() { return $this->context; }
    public function getSql() { return $this->sql; }
    public function getBindings() { return $this->bindings; }
    
    public function toArray()
    {
        return [
            'type' => 'QueryException',
            'message' => $this->getMessage(),
            'sql' => $this->sql,
            'bindings' => $this->bindings,
            'file' => basename($this->getFile()),
            'line' => $this->getLine()
        ];
    }
}

try {
    throw new SimulatedQueryException(
        "Table 'database.users' doesn't exist",
        "SELECT * FROM users WHERE email = ? AND status = ?",
        ['john@example.com', 'active']
    );
} catch (SimulatedQueryException $e) {
    echo "Enhanced Exception Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "SQL: " . $e->getSql() . "\n";
    echo "Bindings: " . json_encode($e->getBindings()) . "\n";
    echo "Context: " . json_encode($e->getContext()) . "\n\n";
}

// Demo 3: HTTP Exception Simulation
echo "3. HTTP Exception Handling (Simulation):\n";
echo str_repeat('-', 50) . "\n";
class SimulatedHttpException extends Exception
{
    private $statusCode;
    private $headers;
    
    public function __construct($statusCode, $message = '', $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        parent::__construct($message);
    }
    
    public function getStatusCode() { return $this->statusCode; }
    public function getHeaders() { return $this->headers; }
    
    public function toResponse($isAjax = false)
    {
        if ($isAjax) {
            return [
                'error' => true,
                'message' => $this->getMessage(),
                'status_code' => $this->statusCode
            ];
        }
        
        return "HTTP {$this->statusCode}: {$this->getMessage()}";
    }
}

// Test different HTTP exceptions
$httpExceptions = [
    new SimulatedHttpException(404, 'User not found'),
    new SimulatedHttpException(403, 'Access denied'),
    new SimulatedHttpException(422, 'Validation failed', ['errors' => ['email' => 'Invalid format']])
];

foreach ($httpExceptions as $exception) {
    echo "Status: {$exception->getStatusCode()}\n";
    echo "Message: {$exception->getMessage()}\n";
    echo "JSON Response: " . json_encode($exception->toResponse(true)) . "\n";
    echo "HTML Response: " . $exception->toResponse(false) . "\n\n";
}

// Demo 4: Error Logging Simulation
echo "4. Error Logging Demonstration:\n";
echo str_repeat('-', 50) . "\n";
class SimulatedLogger
{
    private $logPath;
    
    public function __construct($logPath = null)
    {
        $this->logPath = $logPath ?: sys_get_temp_dir() . '/framework_demo.log';
    }
    
    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }
    
    public function warning($message, $context = [])
    {
        $this->log('WARNING', $message, $context);
    }
    
    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }
    
    private function log($level, $message, $context)
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
        
        file_put_contents($this->logPath, $logEntry, FILE_APPEND | LOCK_EX);
        echo "Logged ({$level}): {$message}\n";
        
        if (!empty($context)) {
            echo "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
    }
    
    public function getLogPath()
    {
        return $this->logPath;
    }
}

$logger = new SimulatedLogger();

// Log different types of errors
$logger->error('Database connection failed', ['host' => 'localhost', 'database' => 'test']);
$logger->warning('Deprecated method used', ['method' => 'oldFunction()', 'file' => 'UserController.php']);
$logger->info('User logged in', ['user_id' => 123, 'ip' => '192.168.1.1']);

echo "\nLog file created at: " . $logger->getLogPath() . "\n\n";

// Demo 5: Global Error Handler Simulation
echo "5. Global Error Handler Demonstration:\n";
echo str_repeat('-', 50) . "\n";
class SimulatedErrorHandler
{
    private $logger;
    private $debug;
    
    public function __construct($logger, $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }
    
    public function handle(Exception $exception)
    {
        // Log the exception
        $this->logger->error($exception->getMessage(), [
            'type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
        
        // Generate response based on exception type
        if ($exception instanceof SimulatedHttpException) {
            return $this->handleHttpException($exception);
        }
        
        if ($exception instanceof SimulatedQueryException) {
            return $this->handleDatabaseException($exception);
        }
        
        return $this->handleGenericException($exception);
    }
    
    private function handleHttpException(SimulatedHttpException $exception)
    {
        return [
            'type' => 'http',
            'status_code' => $exception->getStatusCode(),
            'message' => $exception->getMessage(),
            'debug' => $this->debug ? $this->getDebugInfo($exception) : null
        ];
    }
    
    private function handleDatabaseException(SimulatedQueryException $exception)
    {
        return [
            'type' => 'database',
            'message' => $this->debug ? $exception->getMessage() : 'Database error occurred',
            'debug' => $this->debug ? $exception->toArray() : null
        ];
    }
    
    private function handleGenericException(Exception $exception)
    {
        return [
            'type' => 'general',
            'message' => $this->debug ? $exception->getMessage() : 'An error occurred',
            'debug' => $this->debug ? $this->getDebugInfo($exception) : null
        ];
    }
    
    private function getDebugInfo(Exception $exception)
    {
        return [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => array_slice($exception->getTrace(), 0, 5) // Limit trace for demo
        ];
    }
}

// Test error handler in debug mode
$debugHandler = new SimulatedErrorHandler($logger, true);
echo "Debug Mode Handler:\n";
$result = $debugHandler->handle(new SimulatedHttpException(404, 'User not found'));
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test error handler in production mode
$prodHandler = new SimulatedErrorHandler($logger, false);
echo "Production Mode Handler:\n";
$result = $prodHandler->handle(new SimulatedQueryException('Syntax error', 'SELECT * FROM'));
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Demo 6: Real Framework Integration Example
echo "6. Framework Integration Example:\n";
echo str_repeat('-', 50) . "\n";

// Simulate how this would integrate with your existing Response class
try {
    $response = $di->get('response');
    
    // Simulate different error response formats
    echo "Current Response class capabilities:\n";
    echo "- JSON Response: " . get_class($response) . "::json()\n";
    echo "- Status Codes: " . get_class($response) . "::setStatusCode()\n";
    echo "- Headers: " . get_class($response) . "::setHeaders()\n";
    
    // Show how enhanced error handling would work with Response
    $errorData = [
        'error' => true,
        'message' => 'Validation failed',
        'errors' => [
            'email' => 'Email is required',
            'password' => 'Password must be at least 8 characters'
        ]
    ];
    
    $response->setStatusCode(422);
    $response->json($errorData);
    
    echo "\nSample error response ready to send:\n";
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "Error accessing response: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Demo completed! This shows how enhanced error handling would:\n";
echo "✓ Provide structured exception types\n";
echo "✓ Include contextual information\n";
echo "✓ Support different response formats\n";
echo "✓ Implement comprehensive logging\n";
echo "✓ Handle debug vs production modes\n";
echo "✓ Integrate with existing framework components\n";
echo str_repeat('=', 60) . "\n";