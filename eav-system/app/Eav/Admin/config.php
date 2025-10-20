<?php

return [
    'eav_admin' => [
        // UI Configuration
        'ui' => [
            'items_per_page' => 25,
            'max_export_rows' => 10000,
            'enable_inline_edit' => true,
            'auto_save_draft' => true,
            'theme' => 'bootstrap5'
        ],
        
        // API Configuration
        'api' => [
            'rate_limit_enabled' => true,
            'rate_limit_per_minute' => 100,
            'token_expiry_days' => 30,
            'cors_enabled' => true,
            'allowed_origins' => ['*'],
            'api_version' => 'v1'
        ],
        
        // Versioning Configuration
        'versioning' => [
            'enabled' => true,
            'retention_days' => 365,
            'auto_cleanup' => true,
            'max_versions_per_entity' => 100
        ],
        
        // Audit Logging Configuration
        'audit' => [
            'enabled' => true,
            'log_read_operations' => false,
            'retention_days' => 730,
            'auto_cleanup' => true
        ],
        
        // Import/Export Configuration
        'import' => [
            'max_file_size_mb' => 50,
            'allowed_formats' => ['csv', 'xlsx', 'json'],
            'batch_size' => 500,
            'temp_directory' => sys_get_temp_dir() . '/eav_imports'
        ],
        
        // Export Configuration
        'export' => [
            'max_rows' => 50000,
            'formats' => ['csv', 'xlsx', 'json', 'xml'],
            'temp_directory' => sys_get_temp_dir() . '/eav_exports',
            'retention_hours' => 24
        ],
        
        // Reporting Configuration
        'reporting' => [
            'enabled' => true,
            'cache_reports' => true,
            'cache_ttl' => 3600,
            'max_execution_time' => 300
        ],
        
        // Security Configuration
        'security' => [
            'require_authentication' => true,
            'enable_rbac' => true,
            'default_role' => 'user',
            'super_admin_role' => 'super_admin'
        ],
        
        // Rate Limiting Configuration
        'rate_limits' => [
            'read' => ['limit' => 300, 'window' => 60],
            'write' => ['limit' => 100, 'window' => 60],
            'bulk' => ['limit' => 10, 'window' => 60],
            'schema' => ['limit' => 5, 'window' => 60],
            'export' => ['limit' => 20, 'window' => 60],
            'storage_driver' => 'file',
            'storage_path' => sys_get_temp_dir() . '/eav_rate_limits'
        ]
    ]
];
