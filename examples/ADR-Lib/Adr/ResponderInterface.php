<?php
// app/Core/Adr/ResponderInterface.php
namespace Core\Adr;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Responder Interface - Converts Domain results to HTTP responses
 * 
 * Responders are responsible for:
 * - Converting Domain results to appropriate HTTP responses
 * - Handling different response formats (JSON, HTML, XML)
 * - Setting appropriate HTTP status codes and headers
 * - Content negotiation based on request
 */
interface ResponderInterface
{
    /**
     * Create HTTP response from domain result
     *
     * @param DomainResult $result Domain operation result
     * @param Request $request Original HTTP request (for content negotiation)
     * @return Response HTTP response
     */
    public function respond(DomainResult $result, Request $request): Response;

    /**
     * Handle successful domain operation
     *
     * @param mixed $data Successful result data
     * @param Request $request Original request
     * @return Response Success response
     */
    public function success($data, Request $request): Response;

    /**
     * Handle domain operation failure
     *
     * @param array $errors Error details
     * @param Request $request Original request
     * @return Response Error response
     */
    public function error(array $errors, Request $request): Response;

    /**
     * Handle domain validation errors
     *
     * @param array $validationErrors Validation error details
     * @param Request $request Original request
     * @return Response Validation error response
     */
    public function validationError(array $validationErrors, Request $request): Response;
}