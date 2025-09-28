<?php
namespace App\Core\Mvc;

use Core\Di\Interface\Container as ContainerInterface;

abstract class AbstractModule implements ModuleInterface
{
    protected $ns;
    protected $config = [];

    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setNs($ns)
    {
        $this->ns = $ns;
        return $this;
    }

    public function initialize()
    { 
    }
}