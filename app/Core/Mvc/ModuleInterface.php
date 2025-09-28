<?php
namespace App\Core\Mvc;

use Core\Di\Interface\Container as ContainerInterface;

interface ModuleInterface
{
    public function register(ContainerInterface $container);
}