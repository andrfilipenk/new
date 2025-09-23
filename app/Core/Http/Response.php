<?php
// app/Core/Http/Response.php
namespace Core\Http;

class Response
{
    protected $content;
    protected $statusCode;
    protected $headers  = [];
    protected $sent     = false;

    // --- HTTP Status Codes ---
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    public function __construct($content = '', int $statusCode = self::HTTP_OK, array $headers = [])
    {
        $this->content      = $content;
        $this->statusCode   = $statusCode;
        $this->headers      = array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers);
    }

    public function json($data, int $statusCode = self::HTTP_OK, array $headers = []): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data));
        return $this;
    }

    public function redirect(string $url, int $statusCode = self::HTTP_FOUND): self
    {
        $this->setHeader('Location', $url);
        $this->setStatusCode($statusCode);
        return $this;
    }

    public function error(string $message = 'Error', int $statusCode = self::HTTP_INTERNAL_SERVER_ERROR): self
    {
        $this->setStatusCode($statusCode);
        $this->setContent($message);
        return $this;
    }

    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent()
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

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function withCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): self
    {
        // This method sets the header directly, which is easier for testing
        // and avoids calling the global setcookie() function prematurely.
        $cookieHeader = urlencode($name) . '=' . urlencode($value)
            . ($expire ? '; expires=' . gmdate('D, d M Y H:i:s T', $expire) : '')
            . ($path ? '; path=' . $path : '')
            . ($domain ? '; domain=' . $domain : '')
            . ($secure ? '; secure' : '')
            . ($httpOnly ? '; httponly' : '');
        $this->headers['Set-Cookie'][] = $cookieHeader;
        return $this;
    }

    public function send(): self
    {
        if ($this->isSent()) {
            return $this;
        }
        // Send status code
        http_response_code($this->statusCode);
        // Send headers
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
        return $this;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function __toString(): string
    {
        return (string) $this->content;
    }
}