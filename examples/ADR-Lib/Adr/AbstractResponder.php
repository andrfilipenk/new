<?php
// app/Core/Adr/AbstractResponder.php
namespace Core\Adr;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Abstract Responder - Base implementation for Responders
 * 
 * Handles common response generation patterns
 */
abstract class AbstractResponder implements ResponderInterface
{
    protected array $defaultHeaders = [
        'Content-Type' => 'application/json'
    ];

    /**
     * Create response based on domain result
     */
    public function respond(DomainResult $result, Request $request): Response
    {
        if ($result->isSuccess()) {
            return $this->success($result->getData(), $request);
        }

        if ($result->hasValidationErrors()) {
            return $this->validationError($result->getValidationErrors(), $request);
        }

        return $this->error($result->getErrors(), $request);
    }

    /**
     * Handle successful responses
     */
    public function success($data, Request $request): Response
    {
        $responseData = $this->formatSuccessData($data);
        $statusCode = $this->getSuccessStatusCode($data);
        
        if ($this->shouldReturnJson($request)) {
            return $this->jsonResponse($responseData, $statusCode);
        }
        
        return $this->htmlResponse($responseData, $request);
    }

    /**
     * Handle error responses
     */
    public function error(array $errors, Request $request): Response
    {
        $responseData = $this->formatErrorData($errors);
        $statusCode = $this->getErrorStatusCode($errors);
        
        if ($this->shouldReturnJson($request)) {
            return $this->jsonResponse($responseData, $statusCode);
        }
        
        return $this->htmlErrorResponse($responseData, $request);
    }

    /**
     * Handle validation error responses
     */
    public function validationError(array $validationErrors, Request $request): Response
    {
        $responseData = $this->formatValidationErrorData($validationErrors);
        
        if ($this->shouldReturnJson($request)) {
            return $this->jsonResponse($responseData, 422);
        }
        
        return $this->htmlValidationErrorResponse($validationErrors, $request);
    }

    /**
     * Create JSON response
     */
    protected function jsonResponse(array $data, int $statusCode): Response
    {
        return (new Response())
            ->json($data, $statusCode)
            ->setHeaders($this->defaultHeaders);
    }

    /**
     * Determine if request expects JSON response
     */
    protected function shouldReturnJson(Request $request): bool
    {
        return $request->isAjax() || 
               str_contains($request->header('Accept', ''), 'application/json') ||
               str_contains($request->uri(), '/api/');
    }

    /**
     * Format successful response data
     */
    protected function formatSuccessData($data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ];
    }

    /**
     * Format error response data
     */
    protected function formatErrorData(array $errors): array
    {
        return [
            'success' => false,
            'errors' => $errors,
            'timestamp' => date('c')
        ];
    }

    /**
     * Format validation error response data
     */
    protected function formatValidationErrorData(array $validationErrors): array
    {
        return [
            'success' => false,
            'errors' => [
                'validation' => $validationErrors
            ],
            'message' => 'Validation failed',
            'timestamp' => date('c')
        ];
    }

    /**
     * Get status code for successful responses
     */
    protected function getSuccessStatusCode($data): int
    {
        return 200; // Override in child classes if needed
    }

    /**
     * Get status code for error responses
     */
    protected function getErrorStatusCode(array $errors): int
    {
        if (isset($errors['not_found'])) return 404;
        if (isset($errors['unauthorized'])) return 401;
        if (isset($errors['forbidden'])) return 403;
        return 500;
    }

    /**
     * Child classes should implement HTML response generation
     */
    abstract protected function htmlResponse(array $data, Request $request): Response;
    abstract protected function htmlErrorResponse(array $data, Request $request): Response;
    abstract protected function htmlValidationErrorResponse(array $validationErrors, Request $request): Response;
}