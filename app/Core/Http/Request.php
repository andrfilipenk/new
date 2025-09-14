<?php

namespace Core\Http;

class Request
{
    protected $get;
    protected $post;
    protected $server;
    protected $headers;
    protected $files;
    protected $json;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->headers = $this->extractHeaders();
        $this->files = $this->normalizeFiles($_FILES);
        $this->json = $this->parseJsonBody();
    }

    public function get(string $key = null, $default = null)
    {
        if ($key === null) return $this->get;
        return $this->get[$key] ?? $default;
    }

    public function post(string $key = null, $default = null)
    {
        if ($key === null) return $this->post;
        return $this->post[$key] ?? $default;
    }

    /**
     * Get input from POST, JSON, or GET, in that order.
     */
    public function input(string $key, $default = null)
    {
        return $this->post[$key] 
            ?? $this->json[$key] 
            ?? $this->get[$key] 
            ?? $default;
    }

    /**
     * Get all input data from POST, JSON, and GET.
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json);
    }

    public function has(string $key): bool
    {
        return isset($this->get[$key]) || isset($this->post[$key]) || isset($this->json[$key]);
    }

    /**
     * Get an uploaded file by its key.
     */
    public function file(string $key): ?UploadedFile
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get the request method, supporting method spoofing.
     */
    public function method(): string
    {
        if (isset($this->post['_method'])) {
            return strtoupper($this->post['_method']);
        }
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'];
        $uri = str_replace(APP_DIR, '', $uri);
        return $uri;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $this->header('User-Agent', '');
    }

    public function header(string $name, $default = null)
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function isSecure(): bool
    {
        return !empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }

    protected function extractHeaders(): array
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

    protected function parseJsonBody(): array
    {
        if (str_contains($this->header('Content-Type', ''), 'application/json')) {
            $body = file_get_contents('php://input');
            return json_decode($body, true) ?: [];
        }
        return [];
    }

    protected function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                // Handle array of files
                foreach ($file['name'] as $i => $name) {
                    $normalized[$key][$i] = new UploadedFile([
                        'name' => $name,
                        'type' => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                        'size' => $file['size'][$i],
                    ]);
                }
            } else {
                $normalized[$key] = new UploadedFile($file);
            }
        }
        return $normalized;
    }
}

// example usage in application code:
// $request = new \Core\Http\Request();
// $name = $request->input('name', 'Guest');
// $file = $request->file('avatar');
// if ($file && $file->isValid()) {
//     $file->moveTo('/path/to/uploads/' . $file->getClientOriginalName());
// }