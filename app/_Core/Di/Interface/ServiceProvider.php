<?php
// app/_Core/Di/Interface/ServiceProvider.php
namespace Core\Di\Interface;

/**
 * Service Provider Interface
 */
interface ServiceProvider
{
    public function register(Container $di): void;
}