<?php

namespace Core\Http;

class Response
{
    protected $content;
    protected $statusCode;
    protected $headers;
    protected $sent = false;

    public function __construct($content = '', int $statusCode = 200)
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = [];
    }

    public function setContent($content): Response
    {
        $this->content = $content;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setStatusCode(int $code): Response
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): Response
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function json($data): Response
    {
        $this->setHeader('Content-Type', 'application/json');
        $this->content = json_encode($data);
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): Response
    {
        $this->setHeader('Location', $url);
        $this->statusCode = $statusCode;
        return $this;
    }

    public function notFound(string $message = 'Not Found'): Response
    {
        $this->statusCode = 404;
        $this->content = $message;
        return $this;
    }

    public function send(): Response
    {
        if ($this->sent) {
            return $this;
        }

        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
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