<?php

namespace Core\Di;

use Closure;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Di\Exception\NotFound;
use Core\Di\Exception\Container as ContainerException;
use ReflectionClass;
use ReflectionParameter;

/**
 * Lightweight Dependency Injection Container with Autowiring
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
        $this->set(ContainerInterface::class, $this);
        $this->set(get_class($this), $this);
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
        unset($this->instances[$id], $this->factories[$id]);
        
        if ($concrete instanceof Closure) {
            $this->factories[$id] = $concrete;
        } else {
            $this->definitions[$id] = $concrete;
        }
    }

    /**
     * Get a service from the container
     */
    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        
        if (isset($this->factories[$id])) {
            return $this->instances[$id] = ($this->factories[$id])($this);
        }
        
        $concrete = $this->definitions[$id] ?? $id;
        
        if ($this->isResolvable($concrete)) {
            $instance = $this->build($concrete);
            return $this->instances[$id] = $instance;
        }
        
        throw new NotFound("Service '{$id}' not found or cannot be resolved.");
    }

    /**
     * Check if service exists
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || isset($this->instances[$id]) || $this->isResolvable($id);
    }

    protected function isResolvable($abstract): bool
    {
        return ($abstract instanceof Closure) || (is_string($abstract) && class_exists($abstract));
    }

    protected function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        if (is_string($concrete) && class_exists($concrete)) {
            return $this->autowire($concrete);
        }

        throw new ContainerException("Cannot resolve service. Invalid definition provided.");
    }

    protected function autowire(string $class)
    {
        $reflector = new ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class {$class} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $class();
        }

        $dependencies = array_map(
            fn(ReflectionParameter $param) => $this->resolveParameter($param),
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    protected function resolveParameter(ReflectionParameter $param)
    {
        $type = $param->getType();

        if ($type && !$type->isBuiltin()) {
            return $this->get($type->getName());
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new ContainerException("Cannot resolve constructor parameter '{$param->getName()}' for class '{$param->getDeclaringClass()->getName()}'.");
    }

    public function register($provider): void
    {
        if (is_string($provider)) {
            $provider = $this->build($provider);
        }
        
        if (method_exists($provider, 'register')) {
            $provider->register($this);
        }
    }
}