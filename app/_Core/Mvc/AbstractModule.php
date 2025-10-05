<?php
// app/_Core/Mvc/AbstractModule.php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;

abstract class AbstractModule implements ModuleInterface
{
    use Injectable, EventAware;

    protected $_name;

    protected $_config = [];

    public function getConfig($key = null, $default = null)
    {
        if ($key === null) {
            return $this->_config;
        }
        return $this->_config[$key] ?? $default;
    }

    public function getRoutes()
    {
        return $this->getConfig('routes', []);
    }

    public function getName()
    {
        return $this->_name;
    }

    public function initialize($name, $config) {
        $this->_name   = $name;
        $this->_config = $config;
        $this->fireEvent('module.onInitialize', $this);
        if ($providers = $this->getConfig(key: 'provider')) {
            foreach ($providers as $provider) {
                $this->getDI()->register($provider);
            }
        }
        $this->fireEvent('module.afterInitialize', $this);
        return $this;
    }

    /**
     * Called after module is bootstrapped
     * Override in child classes to register events, services, etc.
     * 
     * @param \Core\Di\Interface\Container $di
     * @return void
     */
    public function afterBootstrap($di)
    {
        // Override in child classes
    }
}