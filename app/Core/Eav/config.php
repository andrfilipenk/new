<?php
// app/Core/Eav/config.php
return [
    // Master switch for performance features
    'enable_performance_layer' => true,

    // Cache Configuration
    'cache' => [
        // Global cache enable/disable
        'enable' => true,
        
        // Default TTL in seconds
        'default_ttl' => 3600,
        
        // L1: Request-Scoped Cache
        'l1_enable' => true,
        
        // L2: Application Memory Cache
        'l2_enable' => true,
        'l2_driver' => 'apcu', // apcu, static, none
        'l2_ttl' => 900, // 15 minutes
        
        // L3: Persistent Cache
        'l3_enable' => true,
        'l3_driver' => 'file', // file, redis, memcached
        'l3_ttl' => 3600, // 1 hour
        'l3_path' => APP_PATH . '../public/cache/eav/', // For file driver
        
        // L4: Query Result Cache
        'l4_enable' => true,
        'l4_driver' => 'file', // Fallback to file if Redis not available
        'l4_ttl' => 300, // 5 minutes
        
        // Cache key prefix
        'prefix' => 'eav_',
        
        // Serialization format (php, json, igbinary, msgpack)
        'serializer' => 'php',
    ],

    // Flat Table Configuration
    'flat_tables' => [
        'enable' => true,
        
        // Eligibility criteria
        'min_attributes' => 10,
        'read_write_ratio' => 5.0,
        'min_entity_count' => 1000,
        'min_query_frequency' => 100,
        'attribute_consistency_threshold' => 0.8,
        
        // Synchronization
        'sync_mode' => 'immediate', // immediate, deferred, rebuild
        'auto_rebuild' => true,
        'rebuild_schedule' => 'daily',
        
        // Prefix for flat table names
        'table_prefix' => 'eav_flat_',
    ],

    // Batch Operations Configuration
    'batch' => [
        'max_size' => 1000, // Maximum entities per batch
        'chunk_size' => 100, // Query chunk size
        'transaction_mode' => true, // Wrap in transaction
    ],

    // Performance Monitoring
    'monitoring' => [
        'enable' => true,
        'sample_rate' => 1.0, // 1.0 = monitor all requests, 0.1 = 10% sample
        'log_slow_queries' => true,
        'slow_query_threshold' => 200, // milliseconds
        'metrics_storage' => 'file', // file, database, redis
        'metrics_path' => APP_PATH . '../public/logs/eav_metrics.log',
    ],

    // Entity Type Configurations
    'entity_types' => [
        // Example: Product entity type
        'product' => [
            'label' => 'Product',
            'table' => 'eav_entity',
            
            // Performance overrides
            'cache_ttl' => 7200, // 2 hours
            'enable_flat_table' => true,
            'flat_table_sync_mode' => 'immediate',
            'cache_priority' => 'high', // high, normal, low
            'query_cache_enable' => true,
            
            // Attributes
            'attributes' => [
                'name' => [
                    'label' => 'Product Name',
                    'type' => 'varchar',
                    'required' => true,
                    'searchable' => true,
                    'filterable' => true,
                ],
                'sku' => [
                    'label' => 'SKU',
                    'type' => 'varchar',
                    'required' => true,
                    'unique' => true,
                    'searchable' => true,
                ],
                'price' => [
                    'label' => 'Price',
                    'type' => 'decimal',
                    'required' => true,
                    'filterable' => true,
                ],
                'qty' => [
                    'label' => 'Quantity',
                    'type' => 'int',
                    'default' => 0,
                ],
                'status' => [
                    'label' => 'Status',
                    'type' => 'int',
                    'required' => true,
                    'default' => 1,
                    'filterable' => true,
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'text',
                    'required' => false,
                ],
                'weight' => [
                    'label' => 'Weight',
                    'type' => 'decimal',
                ],
                'created_date' => [
                    'label' => 'Created Date',
                    'type' => 'datetime',
                    'required' => true,
                ],
            ],
        ],
        
        // Example: Customer entity type
        'customer' => [
            'label' => 'Customer',
            'table' => 'eav_entity',
            
            'cache_ttl' => 3600,
            'enable_flat_table' => false, // Low read frequency
            'cache_priority' => 'normal',
            
            'attributes' => [
                'firstname' => [
                    'label' => 'First Name',
                    'type' => 'varchar',
                    'required' => true,
                ],
                'lastname' => [
                    'label' => 'Last Name',
                    'type' => 'varchar',
                    'required' => true,
                ],
                'email' => [
                    'label' => 'Email',
                    'type' => 'varchar',
                    'required' => true,
                    'unique' => true,
                ],
                'phone' => [
                    'label' => 'Phone',
                    'type' => 'varchar',
                ],
                'age' => [
                    'label' => 'Age',
                    'type' => 'int',
                ],
            ],
        ],
    ],

    // Database table names
    'tables' => [
        'entity' => 'eav_entity',
        'attribute' => 'eav_attribute',
        'entity_varchar' => 'eav_entity_varchar',
        'entity_int' => 'eav_entity_int',
        'entity_decimal' => 'eav_entity_decimal',
        'entity_datetime' => 'eav_entity_datetime',
        'entity_text' => 'eav_entity_text',
        'flat_metadata' => 'eav_flat_metadata',
    ],
];
