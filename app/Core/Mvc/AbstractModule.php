<?php
// app/Core/Mvc/AbstractModule.php
namespace Core\Mvc;

use Core\Di\Injectable;

abstract class AbstractModule implements ModuleInterface
{
    use Injectable;

    protected $_name;

    protected $_config = [];

    public function setup($name, $config = [])
    {
        $this->_name   = $name;
        $this->_config = $config;
        return $this;
    }

    public function getConfig($key = null, $default = null)
    {
        if ($key === null) {
            return $this->_config;
        }
        return $this->_config[$key] ?? $default;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function initialize() {
        $di = $this->getDI();
        if ($providers = $this->getConfig('provider')) {

            foreach ($providers as $provider) {
                $di->register($provider);
            }
        }
    }
}