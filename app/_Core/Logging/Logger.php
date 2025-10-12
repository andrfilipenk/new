<?php
// app/_Core/Logging/Logger.php
namespace Core\Logging;

use Core\Di\Injectable;

class Logger implements LoggerInterface
{
    use Injectable;

    protected string $logFile;

    protected string $minLevel;

    protected array $levels = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7
    ];

    protected array $buffer = [];
    
    protected int $bufferSize = 10;

    public function __construct()
    {
        $config = $this->getDI()->get('config');
        $this->logFile      = $config['logging']['file'];
        $this->minLevel     = $config['logging']['level'] ?? 'error';
        $this->bufferSize   = $config['logging']['buffer_size'] ?? 10;
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
        $this->rotateLogs();
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if ($this->levels[$level] > $this->levels[$this->minLevel]) {
            return;
        }
        $context = $this->sanitizeContext($this->addDefaultContext($context));
        $log     = sprintf(
            "[%s] %s: %s %s\n",
            (new \DateTime())->format('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            json_encode($context, JSON_UNESCAPED_SLASHES)
        );
        $this->buffer[] = $log;
        if (count($this->buffer) >= $this->bufferSize) {
            $this->flush();
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    protected function addDefaultContext(array $context): array
    {
        $request = $this->getDI()->get('request');
        $session = $this->getDI()->has('session') ? $this->getDI()->get('session') : null;
        return array_merge([
            'request_uri'   => $request ? $request->uri() : '',
            'user_id'       => $session && $session->has('user') ? $session->get('user')['id'] : null,
            'ip'            => $request ? $request->ip() : ''
        ], $context);
    }

    protected function sanitizeContext(array $context): array
    {
        $sensitive = ['password', 'token', 'secret'];
        foreach ($context as $key => $value) {
            if (in_array(strtolower($key), $sensitive, true)) {
                $context[$key] = '***';
            } elseif (is_array($value)) {
                $context[$key] = $this->sanitizeContext($value);
            }
        }
        return $context;
    }

    protected function rotateLogs(): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }
        $maxSize = 5 * 1024 * 1024; // 5MB
        if (filesize($this->logFile) > $maxSize) {
            $date = (new \DateTime())->format('YmdHis');
            rename($this->logFile, $this->logFile . '.' . $date);
        }
    }

    protected function flush(): void
    {
        if ($this->buffer) {
            file_put_contents($this->logFile, implode('', $this->buffer), FILE_APPEND);
            $this->buffer = [];
        }
    }

    public function __destruct()
    {
        $this->flush();
    }
}