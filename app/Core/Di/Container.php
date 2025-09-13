<?php

namespace Core\Di;

use Closure;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Di\Exception\NotFound;



/**
 * Lightweight Dependency Injection Container
 */
class Container implements ContainerInterface
{
    protected $definitions = [];
    protected $instances = [];
    protected $factories = [];
    protected static $default;

    public function __construct(array $definitions = [])
    {
        foreach ($definitions as $id => $definition) {
            $this->set($id, $definition);
        }
        
        self::$default = $this;
        $this->set('di', $this);
    }

    /**
     * Set the default container instance
     */
    public static function setDefault(ContainerInterface $container): void
    {
        self::$default = $container;
    }

    /**
     * Get the default container instance
     */
    public static function getDefault(): ContainerInterface
    {
        if (self::$default === null) {
            self::$default = new self();
        }
        
        return self::$default;
    }

    /**
     * Register a service definition
     */
    public function set(string $id, $concrete): void
    {
        // Remove existing entries
        unset($this->instances[$id], $this->factories[$id]);
        
        if (is_callable($concrete)) {
            $this->factories[$id] = $concrete;
        } elseif (is_object($concrete)) {
            $this->instances[$id] = $concrete;
        } else {
            $this->definitions[$id] = $concrete;
        }
    }

    /**
     * Get a service from the container
     */
    public function get(string $id)
    {
        // Return existing instance if available
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        
        // Create new instance
        if (isset($this->factories[$id])) {
            return $this->instances[$id] = $this->factories[$id]($this);
        }
        
        if (isset($this->definitions[$id])) {
            return $this->instances[$id] = $this->build($this->definitions[$id]);
        }
        
        // Auto-resolve class if it exists
        if (class_exists($id)) {
            return $this->instances[$id] = $this->build($id);
        }
        
        throw new NotFound("Service '{$id}' not found in container");
    }

    /**
     * Check if service exists
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) ||
               isset($this->factories[$id]) ||
               isset($this->definitions[$id]) ||
               class_exists($id);
    }

    /**
     * Build a service from definition
     */
    protected function build($definition)
    {
        if (is_string($definition) && class_exists($definition)) {
            return $this->autowire($definition);
        }
        
        if (is_array($definition) && isset($definition['class'])) {
            return $this->buildFromArray($definition);
        }
        
        return $definition;
    }

    /**
     * Simple autowiring without reflection
     */
    protected function autowire(string $class)
    {
        // For minimal implementation, we'll use simple instantiation
        return new $class();
    }

    /**
     * Build service from array definition
     */
    protected function buildFromArray(array $definition)
    {
        $class = $definition['class'];
        $args = $definition['arguments'] ?? [];
        
        // Resolve arguments
        $resolvedArgs = [];
        foreach ($args as $arg) {
            $resolvedArgs[] = $this->resolveArgument($arg);
        }
        
        return new $class(...$resolvedArgs);
    }

    /**
     * Resolve argument value (could be service reference or literal)
     */
    protected function resolveArgument($arg)
    {
        if (is_string($arg) && strpos($arg, '@') === 0) {
            return $this->get(substr($arg, 1));
        }
        
        return $arg;
    }

    /**
     * Create a factory service (new instance each time)
     */
    public function factory(callable $factory): Closure
    {
        return function() use ($factory) {
            return $factory($this);
        };
    }

    /**
     * Extend a service definition
     */
    public function extend(string $id, callable $extender): void
    {
        $service = $this->get($id);
        $extended = $extender($service, $this);
        
        if ($extended !== null) {
            $this->set($id, $extended);
        }
    }

    /**
     * Register a service provider
     */
    public function register($provider): void
    {
        if (is_string($provider)) {
            $provider = new $provider();
        }
        
        if (method_exists($provider, 'register')) {
            $provider->register($this);
        }
    }

    /**
     * Get all registered service IDs
     */
    public function getServices(): array
    {
        return array_unique(array_merge(
            array_keys($this->definitions),
            array_keys($this->factories),
            array_keys($this->instances)
        ));
    }
}