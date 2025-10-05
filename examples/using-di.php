<?php

class FileLogger {
    public function __construct($path) {}
    public function setEventsManager($em) {}
}
class EchoLogger {}

// 1. Basic Service Registration and Retrieval
// Create a simple container
$di = new Core\Di\Container();
// Register a simple value
$di->set('app.name', 'My App');
// Register an object instance
$di->set('logger', new FileLogger('/path/to/logs'));
// Register a closure (lazy loading)
$di->set('database', function($di) {
    return new PDO('mysql:host=localhost;dbname=myapp', 'user', 'password');
});
// Retrieve services
$appName = $di->get('app.name'); // Returns "My App"
$logger = $di->get('logger');    // Returns the FileLogger instance
$db = $di->get('database');      // Creates and returns PDO instance



// 2. Class Auto-Wiring
class UserRepository
{
    protected $db;
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    public function find($id)
    {
        // Database operations...
    }

    public function create($data = [])
    {
        return $data;
    }
}

class ProductRepository {
    public function __construct($db) {}
}

// The container will automatically instantiate UserRepository
// and inject the PDO dependency when requested
$userRepo = $di->get('UserRepository');

// You can also explicitly register it
$di->set('userRepository', function($di) {
    return new UserRepository($di->get('database'));
});




// 3. Using the Injectable Trait
class UsersController
{
    use Core\Di\Injectable;
    public function indexAction()
    {
        // Access services through the DI container
        $db = $this->di->get('database');
        
        // Or using magic property access
        $users = $this->database->query('SELECT * FROM users')->fetchAll();
        
        return $users;
    }
}

class ProductService
{
    use Core\Di\Injectable;
    public function getFeaturedProducts()
    {
        // Service is automatically injected when accessed
        return $this->database->query(
            'SELECT * FROM products WHERE featured = 1'
        )->fetchAll();
    }
}



class EmailService {
    public function __construct($mail) {}
}

// 4. Factory Services (New Instance Each Time)
// Register a factory service
$di->set('email.factory', function($di) {
    return new EmailService($di->get('config')['mail']);
});

// Each call to get() returns a new instance
$email1 = $di->get('email.factory');
$email2 = $di->get('email.factory');
// $email1 !== $email2

// Regular service returns the same instance
$di->set('email.singleton', function($di) {
    return new EmailService($di->get('config')['mail']);
});

$email3 = $di->get('email.singleton');
$email4 = $di->get('email.singleton');
// $email3 === $email4





// 5. Service Providers for Modular Registration
class DatabaseServiceProvider implements Core\Di\Interface\ServiceProvider
{
    public function register(Core\Di\Interface\Container $di): void
    {
        $di->set('database', function($di) {
            $config = $di->get('config')['database'];
            return new PDO(
                "mysql:host={$config['host']};dbname={$config['name']}",
                $config['user'],
                $config['password']
            );
        });
    }
}

class RedisCache {
    public function __construct($host, $port) {}
}
class CacheServiceProvider implements Core\Di\Interface\ServiceProvider
{
    public function register(Core\Di\Interface\Container $di): void
    {
        $di->set('cache', function($di) {
            $config = $di->get('config')['cache'];
            return new RedisCache($config['host'], $config['port']);
        });
    }
}

// Register service providers
$di->register(new DatabaseServiceProvider());
$di->register(new CacheServiceProvider());




// 6. Configuration-Based Service Setup
// Define services in a configuration array
$services = [
    'config' => [
        'database' => [
            'host' => 'localhost',
            'name' => 'myapp',
            'user' => 'root',
            'password' => ''
        ],
        'cache' => [
            'host' => '127.0.0.1',
            'port' => 6379
        ]
    ],
    
    'database' => function($di) {
        $config = $di->get('config')['database'];
        return new PDO(
            "mysql:host={$config['host']};dbname={$config['name']}",
            $config['user'],
            $config['password']
        );
    },
    
    'cache' => function($di) {
        $config = $di->get('config')['cache'];
        return new RedisCache($config['host'], $config['port']);
    },
    
    'userRepository' => function($di) {
        return new UserRepository($di->get('database'));
    },
    
    'productRepository' => function($di) {
        return new ProductRepository($di->get('database'));
    }
];

// Initialize container with configuration
$di = new Core\Di\Container($services);


class Logger {
    public function __construct($handler, $prefix) {}
}
class LoggingDatabaseDecorator extends Logger {}
class PrefixedCache extends Logger {}

// 7. Extending Existing Services
// Extend the database service to add logging
$di->set('database', function($db, $di) {
    // Wrap the database connection with a logging decorator
    return new LoggingDatabaseDecorator($db, $di->get('logger'));
});

// Extend the cache service to add prefixing
$di->set('cache', function($cache, $di) {
    return new PrefixedCache($cache, 'myapp:');
});




// 8. Complex Service Dependencies
interface CacheInterface {}
interface LoggerInterface {}
class Order {}


class OrderProcessor
{
    protected $db;
    protected $cache;
    protected $email;
    protected $logger;
    
    public function __construct(
        PDO $db, 
        CacheInterface $cache,
        EmailService $email,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->cache = $cache;
        $this->email = $email;
        $this->logger = $logger;
    }
    
    public function process(Order $order)
    {
        // Complex order processing logic
    }
}

// Register the order processor with all dependencies
$di->set('orderProcessor', concrete: function($di) {
    return new OrderProcessor(
        $di->get('database'),
        $di->get('cache'),
        $di->get('email'),
        $di->get('logger')
    );
});





// 9. Environment-Specific Configuration
// Determine environment
$environment = getenv('APP_ENV') ?: 'production';

// Load appropriate configuration
$configFile = __DIR__ . "/config/{$environment}.php";
$config = file_exists($configFile) ? require $configFile : [];

// Initialize container with environment-specific config
$di = new Core\Di\Container([
    'config' => $config,
    
    'database' => function($di) {
        $config = $di->get('config')['database'];
        return new PDO(
            "mysql:host={$config['host']};dbname={$config['name']}",
            $config['user'],
            $config['password']
        );
    },
    
    // Other services...
]);

// Use different implementations based on environment
if ($environment === 'development') {
    $di->set('logger', function() {
        return new EchoLogger(); // Log to output in development
    });
} else {
    $di->set('logger', function() {
        return new FileLogger('/var/log/app.log'); // Log to file in production
    });
}




// 10. Controller with Dependency Injection
class Validator {
    public function validate($val): bool {
        return true;
    }
}


class UserController
{
    use Core\Di\Injectable;
    
    protected $userRepository;
    protected $validator;
    
    public function __construct(UserRepository $userRepository, Validator $validator)
    {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }
    
    public function createAction(\Core\Http\Request $request)
    {
        // Validate input
        $errors = $this->validator->validate($request->post());
        
        if (empty($errors)) {
            // Create user
            $user = $this->userRepository->create($request->post());
            
            // Send welcome email
            $this->di->get('emailService')->sendWelcomeEmail($user);
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'errors' => $errors];
    }
}

// Register controller with dependencies
$di->set('UserController', function($di) {
    return new UserController(
        $di->get('userRepository'),
        $di->get('validator')
    );
});





// 11. Using the Container Builder
// Use the builder for fluent configuration
$di = (new Core\Di\ContainerBuilder())
    ->addDefinition('config', [
        'database' => [
            'host' => 'localhost',
            'name' => 'myapp',
            'user' => 'root',
            'password' => ''
        ]
    ])
    ->addDefinition('database', function($di) {
        $config = $di->get('config')['database'];
        return new PDO(
            "mysql:host={$config['host']};dbname={$config['name']}",
            $config['user'],
            $config['password']
        );
    })
    ->addDefinition('userRepository', function($di) {
        return new UserRepository($di->get('database'));
    })
    ->addProvider(new DatabaseServiceProvider())
    ->addProvider(new CacheServiceProvider())
    ->build();



// 12. Testing with Mock Dependencies

// In your test setup
class UnitTest {

    public $di;
    public $userService;

    public function createMock($class) {
        return $class;
    }

    public function once() {}

    public function assertTrue($result) {}



    public function setUp(): void
    {
        $this->di = new Core\Di\Container();
        
        // Mock the database connection
        $mockDb = $this->createMock(PDO::class);
        $this->di->set('database', $mockDb);
        
        // Mock the logger
        $mockLogger = $this->createMock(LoggerInterface::class);
        $this->di->set('logger', $mockLogger);
        
        // Create service with mocked dependencies
        $this->userService = new UserService(
            $this->di->get('database'),
            $this->di->get('logger')
        );
    }

    public function testUserCreation()
    {
        // Configure mock expectations
        $this->di->get('database')
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->createMock(PDOStatement::class));
        
        // Test the service
        $result = $this->userService->createUser(['name' => 'John']);
        
        // Assert results
        $this->assertTrue($result);
    }
}