<?php
// app/Core/Adr/ActionInterface.php
namespace Core\Adr;

use Core\Http\Request;

/**
 * Action Interface - Handles HTTP input and passes data to Domain
 * 
 * Actions are responsible for:
 * - Extracting and validating input from HTTP requests
 * - Converting HTTP data to Domain DTOs
 * - Handling HTTP-specific concerns (authentication, validation)
 * - Calling appropriate Domain services
 */
interface ActionInterface
{
    /**
     * Execute the action with HTTP request
     *
     * @param Request $request HTTP request object
     * @return mixed Domain result to be handled by Responder
     */
    public function execute(Request $request);

    /**
     * Validate input data before processing
     *
     * @param array $data Input data to validate
     * @return array Validation errors (empty if valid)
     */
    public function validate(array $data): array;

    /**
     * Transform HTTP input to Domain DTO
     *
     * @param Request $request HTTP request
     * @return object Domain DTO
     */
    public function createDto(Request $request): object;
}