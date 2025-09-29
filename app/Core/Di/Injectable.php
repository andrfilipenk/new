<?php
// app/Core/Di/Injectable.php
namespace Core\Di;

use Core\Di\Interface\Container as ContainerInterface;

/**
 * Injectable trait for automatic DI integration
 */
trait Injectable
{
    /**
     * Holds Container
     *
     * @var Container
     */
    protected $di;

    public function setDI(ContainerInterface $di): void
    {
        $this->di = $di;
    }

    /**
     * Returns di-container
     *
     * @return Container
     */
    public function getDI(): ContainerInterface
    {
        if ($this->di === null) {
            $this->di = Container::getDefault();
        }
        return $this->di;
    }

    /**
     * Magic method to access services as properties
     */
    public function __get(string $property)
    {
        if ($this->di && $this->di->has($property)) {
            return $this->di->get($property);
        }
        trigger_error("Undefined property: $property", E_USER_NOTICE);
        return null;
    }

    /**
     * Magic method to check if service exists
     */
    public function __isset(string $property): bool
    {
        return $this->di && $this->di->has($property);
    }
}