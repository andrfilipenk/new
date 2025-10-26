<?php

namespace Core;

/**
 * HTTP Response Handler
 * 
 * Encapsulates HTTP response data and provides methods
 * to set headers, status codes, and send responses
 */
class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected mixed $content = '';
    protected array $flashData = [];
    protected static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    /**
     * Set HTTP status code
     * 
     * @param int $code
     * @return static
     */
    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get HTTP status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set response content
     * 
     * @param mixed $content
     * @return static
     */
    public function setContent(mixed $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get response content
     * 
     * @return mixed
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Set header
     * 
     * @param string $name
     * @param string $value
     * @return static
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers
     * 
     * @param array $headers
     * @return static
     */
    public function setHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Get header value
     * 
     * @param string $name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all headers
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Send HTTP headers
     * 
     * @return static
     */
    protected function sendHeaders(): static
    {
        if (headers_sent()) {
            return $this;
        }

        // Send status line
        $statusText = self::$statusTexts[$this->statusCode] ?? 'Unknown';
        header("HTTP/1.1 {$this->statusCode} {$statusText}");

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        return $this;
    }



    /**
     * Create JSON response
     * 
     * @param mixed $data
     * @param int $status
     * @return static
     */
    public static function json(mixed $data, int $status = 200): static
    {
        $response = new static();
        $response->setStatusCode($status);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        return $response;
    }

    /**
     * Create success JSON response
     * 
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return static
     */
    public static function success($data = null, string $message = 'Success', int $status = 200): static
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'timestamp' => date('Y-m-d\TH:i:s\Z')
            ]
        ], $status);
    }

    /**
     * Create error JSON response
     * 
     * @param string $message
     * @param mixed $errors
     * @param int $status
     * @return static
     */
    public static function error(string $message = 'Error', $errors = null, int $status = 400): static
    {
        $data = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d\TH:i:s\Z')
        ];

        if ($errors !== null) {
            $data['errors'] = $errors;
        }

        return self::json($data, $status);
    }

    /**
     * Create validation error response
     * 
     * @param array $errors
     * @param string $message
     * @return static
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): static
    {
        return self::error($message, $errors, 422);
    }

    /**
     * Create HTML response
     * 
     * @param string $content
     * @param int $status
     * @return static
     */
    public static function html(string $content, int $status = 200): static
    {
        $response = new static();
        $response->setStatusCode($status);
        $response->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->setContent($content);
        
        return $response;
    }

    /**
     * Create redirect response
     * 
     * @param string $url
     * @param int $status
     * @return static
     */
    public static function redirect(string $url, int $status = 302): static
    {
        $response = new static();
        $response->setStatusCode($status);
        $response->setHeader('Location', $url);
        
        return $response;
    }

    /**
     * Create download response
     * 
     * @param string $filePath
     * @param string|null $fileName
     * @return static
     */
    public static function download(string $filePath, ?string $fileName = null): static
    {
        if (!file_exists($filePath)) {
            return self::error('File not found', null, 404);
        }

        $fileName = $fileName ?? basename($filePath);
        $mimeType = mime_content_type($filePath);

        $response = new static();
        $response->setStatusCode(200);
        $response->setHeaders([
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => filesize($filePath),
        ]);
        $response->setContent(file_get_contents($filePath));
        
        return $response;
    }

    /**
     * Create no content response
     * 
     * @return static
     */
    public static function noContent(): static
    {
        $response = new static();
        $response->setStatusCode(204);
        return $response;
    }

    /**
     * Create unauthorized response
     * 
     * @param string $message
     * @return static
     */
    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return self::error($message, null, 401);
    }

    /**
     * Create forbidden response
     * 
     * @param string $message
     * @return static
     */
    public static function forbidden(string $message = 'Forbidden'): static
    {
        return self::error($message, null, 403);
    }

    /**
     * Create not found response
     * 
     * @param string $message
     * @param array|null $debug Debug information (only shown in debug mode)
     * @return static
     */
    public static function notFound(string $message = 'Not Found', ?array $debug = null): static
    {
        $data = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d\TH:i:s\Z')
        ];

        // Include debug information if in debug mode
        if (config('app.debug', false) && $debug !== null) {
            $data['debug'] = $debug;
        }

        return self::json($data, 404);
    }

    /**
     * Flash data to session for next request
     * 
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function with(string $key, mixed $value): static
    {
        $this->flashData[$key] = $value;
        return $this;
    }

    /**
     * Get flash data
     * 
     * @return array
     */
    public function getFlashData(): array
    {
        return $this->flashData;
    }

    /**
     * Send response with flash data
     * 
     * @return void
     */
    public function send(): void
    {
        // Store flash data in session if any
        if (!empty($this->flashData)) {
            foreach ($this->flashData as $key => $value) {
                $_SESSION['flash'][$key] = $value;
            }
        }

        $this->sendHeaders();
        echo $this->content;
    }
}
