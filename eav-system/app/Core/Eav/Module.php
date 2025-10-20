<?php
// app/Core/Eav/Module.php
namespace Core\Eav;

/**
 * EAV Module Definition
 * 
 * Entity-Attribute-Value Pattern Implementation with Performance Optimization
 * Phase 4: Multi-level Caching, Flat Tables, Batch Operations, Performance Monitoring
 */
class Module
{
    /**
     * Module name
     */
    public static function getName(): string
    {
        return 'Core\\Eav';
    }

    /**
     * Module version
     */
    public static function getVersion(): string
    {
        return '4.0.0';
    }

    /**
     * Module description
     */
    public static function getDescription(): string
    {
        return 'EAV Library with Performance Enhancement - Multi-level Caching, Flat Tables, Batch Operations';
    }

    /**
     * Get module dependencies
     */
    public static function getDependencies(): array
    {
        return [
            'Core\\Database',
            'Core\\Events',
            'Core\\Di',
        ];
    }
}
