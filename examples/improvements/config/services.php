<?php
// config/services.php
return [
    // Authentication Service
    'auth' => [
        'class' => 'Core\Auth\AuthService',
        'shared' => true
    ],
    
    // Validation Service
    'validation' => [
        'class' => 'Core\Validation\ValidationService',
        'shared' => true
    ],
    
    // Mail Service
    'mail' => [
        'class' => 'Core\Mail\MailService',
        'arguments' => [
            [
                'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
                'port' => $_ENV['MAIL_PORT'] ?? 587,
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
                'from' => [
                    'email' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@company.com',
                    'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Company System'
                ]
            ]
        ]
    ],
    
    // Cache Service
    'cache' => [
        'class' => 'Core\Cache\CacheService',
        'arguments' => [
            [
                'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
                'prefix' => $_ENV['CACHE_PREFIX'] ?? 'app_',
                'ttl' => $_ENV['CACHE_TTL'] ?? 3600,
                'path' => APP_PATH . '../storage/cache'
            ]
        ]
    ],
    
    // File Upload Service
    'upload' => [
        'class' => 'Core\Upload\UploadService',
        'arguments' => [
            [
                'upload_path' => APP_PATH . '../public/uploads',
                'max_size' => $_ENV['UPLOAD_MAX_SIZE'] ?? 10485760, // 10MB
                'allowed_types' => [
                    'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                    'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
                    'archive' => ['zip', 'rar', '7z']
                ]
            ]
        ]
    ],
    
    // Notification Service
    'notification' => [
        'class' => 'Core\Notification\NotificationService',
        'arguments' => [
            [
                'channels' => ['database', 'email'],
                'default_channel' => 'database'
            ]
        ]
    ],
    
    // Logger Service
    'logger' => [
        'class' => 'Core\Logger\LoggerService',
        'arguments' => [
            [
                'name' => 'app',
                'path' => APP_PATH . '../storage/logs/app.log',
                'level' => $_ENV['LOG_LEVEL'] ?? 'info',
                'max_files' => 7 // Keep 7 days of logs
            ]
        ]
    ],
    
    // Report Service
    'report' => [
        'class' => 'Core\Report\ReportService',
        'shared' => true
    ],
    
    // Backup Service
    'backup' => [
        'class' => 'Core\Backup\BackupService',
        'arguments' => [
            [
                'storage_path' => APP_PATH . '../storage/backups',
                'database_backup' => true,
                'files_backup' => true,
                'max_backups' => 10 // Keep 10 latest backups
            ]
        ]
    ]
];