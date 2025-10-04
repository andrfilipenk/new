<?php
// examples/navigation-test.php

// Include bootstrap
require __DIR__ . '/../app/bootstrap.php';

// Test the navigation system
try {
    echo "Testing Event-Driven Navigation System\n";
    echo "=====================================\n\n";
    
    // Get DI container from global scope
    global $di;
    
    if (!$di) {
        echo "Error: DI container not available\n";
        exit(1);
    }
    
    echo "DI container available: " . get_class($di) . "\n";
    
    // Debug service provider registration
    echo "\nDebugging service registration...\n";
    
    // Test basic service registration first
    echo "Testing basic service registration...\n";
    $di->set('testService', function() { return 'test'; });
    echo "Test service available: " . ($di->has('testService') ? 'YES' : 'NO') . "\n";
    if ($di->has('testService')) {
        echo "Test service value: " . $di->get('testService') . "\n";
    }
    
    echo "Manually registering NavigationServiceProvider...\n";
    try {
        $navProvider = new \Base\Provider\NavigationServiceProvider();
        echo "About to call register method...\n";
        $navProvider->register($di);
        echo "NavigationServiceProvider registered successfully\n";
        
        // Check immediately after registration
        echo "Checking services immediately after registration...\n";
        echo "NavigationBuilder available: " . ($di->has('navigationBuilder') ? 'YES' : 'NO') . "\n";
        echo "Navigation available: " . ($di->has('navigation') ? 'YES' : 'NO') . "\n";
        
        // Try to get NavigationBuilder manually
        if ($di->has('navigationBuilder')) {
            echo "Trying to get navigationBuilder...\n";
            $builder = $di->get('navigationBuilder');
            echo "NavigationBuilder retrieved: " . get_class($builder) . "\n";
        }
    } catch (Exception $e) {
        echo "Error registering NavigationServiceProvider: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    } catch (Error $e) {
        echo "Fatal Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    // Initialize modules to trigger afterBootstrap
    echo "\nInitializing modules...\n";
    $config = $di->get('config');
    foreach ($config['app']['module'] as $module) {
        $moduleNs = $module . '\\';
        $moduleClass = $moduleNs . 'Module';
        echo "Loading module: $moduleClass\n";
        
        if (class_exists($moduleClass)) {
            $moduleInstance = new $moduleClass();
            $moduleInstance->setDI($di);
            
            // Set events manager
            if ($di->has('eventsManager')) {
                $moduleInstance->setEventsManager($di->get('eventsManager'));
            }
            
            // Call afterBootstrap manually
            if (method_exists($moduleInstance, 'afterBootstrap')) {
                echo "Calling afterBootstrap for $moduleClass\n";
                $moduleInstance->afterBootstrap($di);
            }
        } else {
            echo "Module class $moduleClass not found\n";
        }
    }
    
    echo "\nTesting NavigationBuilder...\n";
    // Test if NavigationBuilder can be created
    if ($di->has('navigationBuilder')) {
        echo "NavigationBuilder service registered\n";
        $builder = $di->get('navigationBuilder');
        echo "NavigationBuilder created: " . get_class($builder) . "\n";
    } else {
        echo "NavigationBuilder service NOT registered\n";
    }
    
    echo "\nTesting Navigation service...\n";
    // Test if navigation service can be created
    if ($di->has('navigation')) {
        echo "Navigation service registered\n";
        $navigation = $di->get('navigation');
        echo "Navigation created successfully\n";
        
        echo "\nNavigation Structure:\n";
        print_r($navigation);
        
        echo "\nNavigation Groups Summary:\n";
        foreach ($navigation as $groupName => $group) {
            echo "Group: {$groupName} (Weight: {$group['config']['weight']})\n";
            echo "  Items count: " . count($group['items']) . "\n";
            foreach ($group['items'] as $item) {
                echo "    - {$item['label']} ({$item['module']}) [Weight: {$item['weight']}]\n";
                if (!empty($item['children'])) {
                    foreach ($item['children'] as $child) {
                        echo "      └─ {$child['label']} ({$child['module']})\n";
                    }
                }
            }
            echo "\n";
        }
    } else {
        echo "Navigation service NOT registered\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}