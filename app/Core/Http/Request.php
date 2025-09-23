<?php
// app/Core/Http/Request.php
namespace Core\Http;

/**
 * Optimized Request class with lazy loading and reduced memory footprint
 */
class Request
{
    private static $instance;
    private array $get;
    private array $post;
    private array $server;
    private ?array $headers = null;
    private ?array $files = null;
    private ?array $json = null;
    private ?array $merged = null;

    public function __construct()
    {
        // Only capture essential data immediately
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
    }

    public static function capture(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(?string $key = null, $default = null)
    {
        return $key === null ? $this->get : ($this->get[$key] ?? $default);
    }

    public function post(?string $key = null, $default = null)
    {
        return $key === null ? $this->post : ($this->post[$key] ?? $default);
    }

    public function input(string $key, $default = null)
    {
        // Check in order of preference with short-circuit evaluation
        return $this->post[$key] 
            ?? $this->getJson()[$key] 
            ?? $this->get[$key] 
            ?? $default;
    }
    
    public function all(): array
    {
        // Lazy merge with caching
        return $this->merged ??= array_merge($this->get, $this->post, $this->getJson());
    }

    public function has(string $key): bool
    {
        return isset($this->get[$key]) || isset($this->post[$key]) || isset($this->getJson()[$key]);
    }
    
    public function file(string $key): ?UploadedFile
    {
        return $this->getFiles()[$key] ?? null;
    }

    public function method(): string
    {
        // Cache method override check
        static $method = null;
        if ($method === null) {
            $method = isset($this->post['_method']) 
                ? strtoupper($this->post['_method'])
                : ($this->server['REQUEST_METHOD'] ?? 'GET');
        }
        return $method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function uri($base = null): string
    {
        static $uri = null;
        if ($uri === null) {
            $uri = $this->server['REQUEST_URI'];
            if ($base !== null) {
                $uri = str_replace($base, '', $uri);
            }
        }
        return $uri;
    }

    public function ip(): string
    {
        // Check for forwarded IP with fallback
        return $this->server['HTTP_X_FORWARDED_FOR'] 
            ?? $this->server['HTTP_X_REAL_IP'] 
            ?? $this->server['REMOTE_ADDR'] 
            ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $this->header('User-Agent') ?? '';
    }

    public function header(string $name, $default = null)
    {
        return $this->getHeaders()[strtolower($name)] ?? $default;
    }

    public function isAjax(): bool
    {
        return strcasecmp($this->header('X-Requested-With') ?? '', 'xmlhttprequest') === 0;
    }

    public function isSecure(): bool
    {
        return !empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type') ?? '', 'application/json');
    }

    // Lazy loading methods
    private function getHeaders(): array
    {
        if ($this->headers === null) {
            $this->headers = $this->extractHeaders();
        }
        return $this->headers;
    }

    private function getJson(): array
    {
        if ($this->json === null) {
            $this->json = $this->isJson() ? $this->parseJsonBody() : [];
        }
        return $this->json;
    }

    private function getFiles(): array
    {
        if ($this->files === null) {
            $this->files = empty($_FILES) ? [] : $this->normalizeFiles($_FILES);
        }
        return $this->files;
    }

    private function extractHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return array_change_key_case(getallheaders(), CASE_LOWER);
        }
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    private function parseJsonBody(): array
    {
        $body = file_get_contents('php://input');
        return $body ? (json_decode($body, true) ?: []) : [];
    }

    private function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                foreach ($file['name'] as $i => $name) {
                    $normalized[$key][$i] = new UploadedFile([
                        'name'      => $name,
                        'type'      => $file['type'][$i],
                        'tmp_name'  => $file['tmp_name'][$i],
                        'error'     => $file['error'][$i],
                        'size'      => $file['size'][$i],
                    ]);
                }
            } else {
                $normalized[$key] = new UploadedFile($file);
            }
        }
        return $normalized;
    }

    // Memory cleanup
    public function __destruct()
    {
        $this->merged = null;
    }
}