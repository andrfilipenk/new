<?php
// app/Eav/config.php

/**
 * EAV Module Configuration
 * 
 * This configuration file defines the EAV module's settings, routes,
 * and service providers.
 */
return [
    /**
     * Service Providers
     * 
     * Providers that will be registered when the module is loaded.
     */
    'provider' => [
        '\Eav\Provider\EavServiceProvider',
    ],

    /**
     * Routes
     * 
     * Define any admin/management routes for the EAV module.
     * Most EAV operations are programmatic, but you can add
     * admin interfaces here if needed.
     */
    'routes' => [
        // Example: EAV management interface
        // '/admin/eav/entities' => [
        //     'module'     => 'Eav',
        //     'controller' => 'Admin',
        //     'action'     => 'entities',
        //     'method'     => 'GET'
        // ],
    ],

    /**
     * Global EAV Configuration
     * 
     * These settings control the behavior of the EAV system.
     */
    'eav' => [
        /**
         * Table prefix for all EAV tables
         */
        'table_prefix' => 'eav_',

        /**
         * Automatic schema synchronization
         * 
         * When enabled, the system will automatically detect configuration
         * changes and synchronize the database schema.
         */
        'auto_sync' => true,

        /**
         * Synchronization mode
         * 
         * - immediate: Apply changes immediately when detected
         * - deferred: Queue changes for batch processing
         * - manual: Require manual synchronization trigger
         */
        'sync_mode' => 'immediate',

        /**
         * Create backup before schema synchronization
         */
        'backup_before_sync' => false,

        /**
         * Maximum index key length in bytes
         * 
         * This is important for MySQL/MariaDB compatibility,
         * especially when using utf8mb4 charset.
         */
        'max_index_length' => 767,

        /**
         * Enable flat table generation for performance
         * 
         * Flat tables denormalize EAV data into single tables
         * for improved read performance.
         */
        'use_flat_tables' => true,

        /**
         * Minimum attributes for flat table consideration
         * 
         * Entity types with fewer attributes than this threshold
         * will not generate flat tables.
         */
        'flat_table_threshold' => 10,

        /**
         * Enable query result caching
         */
        'enable_cache' => true,

        /**
         * Default cache TTL in seconds
         */
        'cache_ttl' => 3600,

        /**
         * Entity configuration directory
         * 
         * Where to find entity configuration files
         */
        'config_path' => __DIR__ . '/Config/entities',
    ],
];
