<?php

namespace Core;

/**
 * HTTP Request Handler
 * 
 * Encapsulates HTTP request data and provides convenient methods
 * to access request parameters, headers, and other request information
 */
class Request
{
    protected array $query = [];
    protected array $post = [];
    protected array $server = [];
    protected array $headers = [];
    protected array $cookies = [];
    protected array $files = [];
    protected ?string $body = null;
    protected array $parsedBody = [];
    protected string $method;
    protected string $uri;
    protected string $path;
    protected array $routeParams = [];
    protected string $basePath = '';

    /**
     * Create new Request instance from globals
     * 
     * @return static
     */
    public static function capture(): static
    {
        $request = new static();
        $request->query = $_GET;
        $request->post = $_POST;
        $request->server = $_SERVER;
        $request->cookies = $_COOKIE;
        $request->files = $_FILES;
        $request->headers = $request->extractHeaders();
        $request->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $request->uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Detect base path from SCRIPT_NAME
        $request->basePath = $request->detectBasePath();
        
        // Normalize path by removing base path
        $request->path = $request->normalizePath($request->uri);
        
        $request->parseBody();
        
        return $request;
    }

    /**
     * Extract headers from server variables
     * 
     * @return array
     */
    protected function extractHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        
        // Add content type and length if present
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['CONTENT-TYPE'] = $this->server['CONTENT_TYPE'];
        }
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['CONTENT-LENGTH'] = $this->server['CONTENT_LENGTH'];
        }
        
        return $headers;
    }

    /**
     * Parse request body for JSON and other content types
     * 
     * @return void
     */
    protected function parseBody(): void
    {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $contentType = $this->header('CONTENT-TYPE', '');
            
            if (str_contains($contentType, 'application/json')) {
                $this->body = file_get_contents('php://input');
                $this->parsedBody = json_decode($this->body, true) ?? [];
            }
        }
    }

    /**
     * Get HTTP method
     * 
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get request URI
     * 
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Get request URI (alias)
     * 
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get request path
     * 
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Check if request method matches
     * 
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * Get input value from request
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, $default = null): mixed
    {
        $data = array_merge($this->query, $this->post, $this->parsedBody);
        
        if ($key === null) {
            return $data;
        }
        
        return $data[$key] ?? $default;
    }

    /**
     * Get query parameter
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function query(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        
        return $this->query[$key] ?? $default;
    }

    /**
     * Get POST parameter
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function post(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        
        return $this->post[$key] ?? $default;
    }

    /**
     * Get all request data
     * 
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->parsedBody);
    }

    /**
     * Get specific keys from request
     * 
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array
    {
        $data = $this->all();
        return array_intersect_key($data, array_flip($keys));
    }

    /**
     * Get request except specific keys
     * 
     * @param array $keys
     * @return array
     */
    public function except(array $keys): array
    {
        $data = $this->all();
        return array_diff_key($data, array_flip($keys));
    }

    /**
     * Check if request has key
     * 
     * @param string|array $key
     * @return bool
     */
    public function has(string|array $key): bool
    {
        $keys = is_array($key) ? $key : [$key];
        $data = $this->all();
        
        foreach ($keys as $k) {
            if (!array_key_exists($k, $data)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get header value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function header(string $key, $default = null): mixed
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get all headers
     * 
     * @return array
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get cookie value
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function cookie(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->cookies;
        }
        
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get uploaded file
     * 
     * @param string $key
     * @return array|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if file was uploaded
     * 
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get server variable
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function server(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->server;
        }
        
        return $this->server[$key] ?? $default;
    }

    /**
     * Get client IP address
     * 
     * @return string|null
     */
    public function ip(): ?string
    {
        return $this->server('REMOTE_ADDR');
    }

    /**
     * Get user agent
     * 
     * @return string|null
     */
    public function userAgent(): ?string
    {
        return $this->server('HTTP_USER_AGENT');
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('X-REQUESTED-WITH') === 'XMLHttpRequest';
    }

    /**
     * Check if request expects JSON
     * 
     * @return bool
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('ACCEPT', '');
        return str_contains($accept, 'application/json');
    }

    /**
     * Check if request expects HTML
     * 
     * @return bool
     */
    public function expectsHtml(): bool
    {
        if ($this->isAjax() || $this->expectsJson()) {
            return false;
        }
        
        $accept = $this->header('ACCEPT', '');
        return str_contains($accept, 'text/html') || empty($accept);
    }

    /**
     * Set route parameters
     * 
     * @param array $params
     * @return void
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Get route parameter
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function route(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->routeParams;
        }
        
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Get raw request body
     * 
     * @return string|null
     */
    public function getBody(): ?string
    {
        if ($this->body === null) {
            $this->body = file_get_contents('php://input');
        }
        
        return $this->body;
    }

    /**
     * Detect base path from SCRIPT_NAME
     * 
     * @return string
     */
    protected function detectBasePath(): string
    {
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        
        if (empty($scriptName)) {
            return '';
        }
        
        // Remove /index.php from the end to get the base directory
        $basePath = str_replace('\\', '/', dirname($scriptName));
        
        // Normalize to empty string if it's just the root
        if ($basePath === '/' || $basePath === '.') {
            return '';
        }
        
        return $basePath;
    }

    /**
     * Normalize request path by removing base path
     * 
     * @param string $uri
     * @return string
     */
    protected function normalizePath(string $uri): string
    {
        // Extract path from URI (removes query string and fragments)
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        
        // Remove base path if present
        if (!empty($this->basePath) && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
        }
        
        // Ensure path starts with /
        if (empty($path) || $path[0] !== '/') {
            $path = '/' . $path;
        }
        
        // Normalize double slashes to single slash
        $path = preg_replace('#/+#', '/', $path);
        
        // Remove trailing slash unless it's the root path
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        
        return $path;
    }

    /**
     * Get base path
     * 
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath;
    }
}
