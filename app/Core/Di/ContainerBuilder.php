<?php
// app/Core/Di/ContainerBuilder.php
namespace Core\Di;

use Core\Di\Interface\Container as ContainerInterface;
/**
 * Container builder for fluent configuration
 */
class ContainerBuilder
{
    protected $definitions  = [];
    protected $providers    = [];

    public function addDefinition(string $id, $concrete): self
    {
        $this->definitions[$id] = $concrete;
        return $this;
    }

    public function addDefinitions(array $definitions): self
    {
        $this->definitions = array_merge($this->definitions, $definitions);
        return $this;
    }

    public function addProvider($provider): self
    {
        $this->providers[] = $provider;
        return $this;
    }

    public function build(): ContainerInterface
    {
        $container = new Container($this->definitions);
        // Register providers
        foreach ($this->providers as $provider) {
            $container->register($provider);
        }
        return $container;
    }
}