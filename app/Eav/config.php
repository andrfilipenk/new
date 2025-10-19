<?php
// app/Eav/config.php

return [
    /**
     * EAV Module Configuration
     */
    'eav' => [
        /**
         * Default entity type code for dynamic entities
         */
        'default_entity_type' => 'product',

        /**
         * Cache configuration
         */
        'cache' => [
            'enabled' => true,
            'ttl' => 3600, // 1 hour
            'prefix' => 'eav:',
            'schema_ttl' => 7200, // 2 hours
            'entity_ttl' => 1800, // 30 minutes
            'query_ttl' => 600, // 10 minutes
        ],

        /**
         * Batch processing configuration
         */
        'batch' => [
            'chunk_size' => 1000,
            'max_batch_size' => 5000,
        ],

        /**
         * Query optimization settings
         */
        'query' => [
            'max_joins' => 10,
            'optimize_joins' => true,
            'use_subqueries' => true,
        ],

        /**
         * Value storage configuration
         */
        'storage' => [
            'tables' => [
                'varchar' => 'eav_values_varchar',
                'int' => 'eav_values_int',
                'decimal' => 'eav_values_decimal',
                'text' => 'eav_values_text',
                'datetime' => 'eav_values_datetime',
            ],
            'varchar_length' => 255,
        ],

        /**
         * Index settings
         */
        'index' => [
            'enabled' => true,
            'auto_index_searchable' => true,
            'rebuild_on_schema_change' => true,
        ],

        /**
         * Event settings
         */
        'events' => [
            'enabled' => true,
            'async' => false,
        ],

        /**
         * Validation rules
         */
        'validation' => [
            'strict_mode' => true,
            'validate_on_save' => true,
        ],

        /**
         * Performance settings
         */
        'performance' => [
            'eager_load_attributes' => true,
            'lazy_load_values' => false,
            'preload_common_attributes' => true,
        ],
    ],
];
