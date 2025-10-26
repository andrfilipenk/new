<?php

namespace Core\Logger;

use Core\Config\Config;

/**
 * Logger
 * 
 * Multi-level logging system with multiple handlers
 */
class Logger
{
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const CRITICAL = 'critical';

    protected static array $handlers = [];
    protected static array $levels = [
        self::DEBUG => 0,
        self::INFO => 1,
        self::WARNING => 2,
        self::ERROR => 3,
        self::CRITICAL => 4,
    ];

    /**
     * Initialize logger
     */
    public static function init(): void
    {
        $logPath = Config::get('app.storage_path', BASE_PATH . '/storage') . '/logs';
        
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        self::$handlers = [
            new FileHandler($logPath),
        ];
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log message
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        if (empty(self::$handlers)) {
            self::init();
        }

        $minLevel = Config::get('app.log_level', self::DEBUG);
        
        if (self::$levels[$level] < self::$levels[$minLevel]) {
            return;
        }

        $record = [
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'datetime' => date('Y-m-d H:i:s'),
            'channel' => 'app',
        ];

        foreach (self::$handlers as $handler) {
            $handler->handle($record);
        }
    }

    /**
     * Add handler
     */
    public static function addHandler($handler): void
    {
        self::$handlers[] = $handler;
    }
}

/**
 * File Handler
 */
class FileHandler
{
    protected string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
    }

    public function handle(array $record): void
    {
        $filename = $this->logPath . '/app-' . date('Y-m-d') . '.log';
        $message = $this->format($record);
        file_put_contents($filename, $message, FILE_APPEND | LOCK_EX);
    }

    protected function format(array $record): string
    {
        $context = !empty($record['context']) ? ' ' . json_encode($record['context']) : '';
        return "[{$record['datetime']}] {$record['level']}: {$record['message']}{$context}\n";
    }
}
