<?php

namespace Core\Di\Interface;

/**
 * Service Provider Interface
 */
interface ServiceProvider
{
    public function register(Container $di): void;
}