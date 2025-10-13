<?php
// app/Core/Http/Response.php
namespace Core\Http;

/**
 * Optimized Response class with reduced memory usage and faster operations
 */
class Response
{
    private string $content = '';
    private int $statusCode = 200;
    private array $headers = [];
    private bool $sent = false;

    // Common status codes as static properties to reduce memory
    public const STATUS_CODES = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error'
    ];

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers + ['Content-Type' => 'text/html; charset=UTF-8'];
    }

    public static function create(string $content = '', int $statusCode = 200): self
    {
        return new self($content, $statusCode);
    }

    public function json($data, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->headers['Content-Type'] = 'application/json';
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->headers['Location'] = $url;
        $this->statusCode = $statusCode;
        $this->content = '';
        return $this;
    }

    public function error(string $message = 'Error', int $statusCode = 500): self
    {
        $this->statusCode = $statusCode;
        $this->content = $message;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function withCookie(
        string $name, 
        string $value = '', 
        int $expire = 0, 
        string $path = '/', 
        string $domain = '', 
        bool $secure = false, 
        bool $httpOnly = true
    ): self {
        // Use more efficient cookie header building
        $cookie = $name . '=' . $value;
        if ($expire) $cookie .= '; expires=' . gmdate('D, d M Y H:i:s T', $expire);
        if ($path !== '/') $cookie .= '; path=' . $path;
        if ($domain) $cookie .= '; domain=' . $domain;
        if ($secure) $cookie .= '; secure';
        if ($httpOnly) $cookie .= '; httponly';
        if (!isset($this->headers['Set-Cookie'])) {
            $this->headers['Set-Cookie'] = [];
        }
        $this->headers['Set-Cookie'][] = $cookie;
        return $this;
    }

    public function send(): void
    {
        if ($this->sent) return;
        // Send status code
        http_response_code($this->statusCode);
        // Send headers efficiently
        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header("$name: $v", false);
                }
            } else {
                header("$name: $value");
            }
        }
        // Send content
        echo $this->content;
        $this->sent = true;
        // Optional: flush output buffer for faster response
        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function getSize(): int
    {
        return strlen($this->content);
    }

    public function isEmpty(): bool
    {
        return $this->content === '';
    }

    public function __toString(): string
    {
        return $this->content;
    }
}