<?php

// Basic Event Usage:
// Create event manager
$events = new Core\Events\Manager();

// Attach a listener
$events->attach('user.created', function($event) {
    $user = $event->getData();
    echo "User created: " . $user['name'] . "\n";
});

// Attach another listener with higher priority
$events->attach('user.created', function($event) {
    $user = $event->getData();
    // Send welcome email
    echo "Sending welcome email to: " . $user['email'] . "\n";
}, 10);

// Trigger the event
$events->trigger('user.created', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);






// Using Event-Aware Objects:
class UserService
{
    use Core\Events\EventAware;

    public function __construct($db, $logger) {}
    
    public function createUser(array $data)
    {
        // Create user logic...
        $user = [
            'id' => 1,
            'name' => $data['name'],
            'email' => $data['email']
        ];
        
        // Fire event
        $this->fireEvent('user:created', $user);
        
        return $user;
    }
}

// Setup
$userService = new UserService();
$userService->setEventsManager($events);

// Create user (will trigger events)
$user = $userService->createUser([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com'
]);







// Stopping Event Propagation:
// Attach a listener that stops propagation
$events->attach('request.before', function($event) {
    $request = $event->getData();
    // Check if request is from a banned IP
    if ($request->getClientAddress() === '192.168.1.100') {
        echo "Request from banned IP, stopping processing\n";
        $event->stopPropagation();
        return false;
    }
    return $request;
}, 100); // High priority to run first

// This won't be called if propagation is stopped
$events->attach('request.before', function($event) {
    echo "This won't be called for banned IPs\n";
});

// Trigger the event
$events->trigger('request.before', $request);





// Event Priority Example:
// Lower priority (runs last)
$events->attach('app.shutdown', function() {
    echo "Third\n";
}, 0);

// Higher priority (runs first)
$events->attach('app.shutdown', function() {
    echo "First\n";
}, 100);

// Medium priority (runs second)
$events->attach('app.shutdown', function() {
    echo "Second\n";
}, 50);

// Output: First, Second, Third
$events->trigger('app.shutdown');






// 6. Integration with DI Container:
// In your bootstrap file
$di->set('events', function() {
    return new Core\Events\Manager();
});

// Make events available to other services
$di->set('userService', function() use ($di) {
    $service = new UserService();
    $service->setEventsManager($di->get('events'));
    return $service;
});

$di->set('logger', function() use ($di) {
    $logger = new FileLogger('/path/to/logs');
    $logger->setEventsManager($di->get('events'));
    return $logger;
});






// 7. Advanced Usage with Multiple Events:
// Wildcard event matching
$events->attach('db.*', function($event) {
    echo "Database event: " . $event->getName() . "\n";
});

// Multiple events with the same listener
$events->attach('user.created', function($event) {
    echo "User event: " . $event->getName() . "\n";
});

// Event with return value
$events->attach('response.beforeSend', function($event) {
    $response = $event->getData();
    
    // Add custom header
    $response->setHeader('X-Powered-By', 'My Framework');
    
    return $response; // Return modified response
});

// Get the modified response
$response = $events->trigger('response.beforeSend', $response)->getData();







// 5. Usage Examples with setData():
// Create event manager
$events = new Core\Events\Manager();

// Example 1: Modifying data in an event
$events->attach('response.beforeSend', function($event) {
    $response = $event->getData();
    
    // Add custom header
    $response->setHeader('X-Powered-By', 'My Framework');
    
    // Return modified response (will be set via setData)
    return $response;
});

// Trigger the event and get modified data
$response = new Core\Http\Response();
$event = $events->trigger('response.beforeSend', $response);
$modifiedResponse = $event->getData();

// Example 2: Data transformation
$events->attach('data.process', function($event) {
    $data = $event->getData();
    
    // Transform the data
    $transformed = array_map('strtoupper', $data);
    
    // Return transformed data
    return $transformed;
});

// Trigger data processing event
$data = ['hello', 'world'];
$event = $events->trigger('data.process', $data);
$processedData = $event->getData(); // ['HELLO', 'WORLD']

// Example 3: Chaining modifications
$events->attach('user.beforeSave', function($event) {
    $user = $event->getData();
    
    // Set created timestamp
    $user['created_at'] = time();
    
    // Return modified user
    return $user;
});

$events->attach('user.beforeSave', function($event) {
    $user = $event->getData();
    
    // Hash password if it exists
    if (isset($user['password'])) {
        $user['password'] = password_hash($user['password'], PASSWORD_DEFAULT);
    }
    
    // Return modified user
    return $user;
}, 10); // Higher priority to run first

// Trigger user before save event
$userData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret'
];

$event = $events->trigger('user.beforeSave', $userData);
$preparedUser = $event->getData();