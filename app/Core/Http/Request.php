<?php

namespace Core\Http;

class Request
{
    protected $get;
    protected $post;
    protected $server;
    protected $headers;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->headers = $this->extractHeaders();
    }

    public function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        
        return $this->get[$key] ?? $default;
    }

    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        
        return $this->post[$key] ?? $default;
    }

    public function input(string $key = null, $default = null)
    {
        if ($key === null) {
            return array_merge($this->get, $this->post);
        }
        
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function path(): string
    {
        $uri = $this->uri();
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        return $uri;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function header(string $name = null, $default = null)
    {
        if ($name === null) {
            return $this->headers;
        }
        
        $name = strtoupper(str_replace('-', '_', $name));
        return $this->headers[$name] ?? $default;
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isSecure(): bool
    {
        return !empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }

    protected function extractHeaders(): array
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$header] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $header = str_replace('_', '-', strtolower($key));
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function has(string $key): bool
    {
        return isset($this->get[$key]) || isset($this->post[$key]);
    }
}