<?php
// app/Core/Adr/AbstractAction.php
namespace Core\Adr;

use Core\Http\Request;
use Core\Validation\Validator;

/**
 * Abstract Action - Base implementation for Actions
 * 
 * Provides common functionality for handling HTTP requests and validation
 */
abstract class AbstractAction implements ActionInterface
{
    protected Validator $validator;
    protected DomainInterface $domain;

    public function __construct(DomainInterface $domain, Validator $validator = null)
    {
        $this->domain = $domain;
        $this->validator = $validator ?? new Validator();
    }

    /**
     * Execute the action with proper error handling
     */
    public function execute(Request $request)
    {
        try {
            // Extract and validate input
            $inputData = $this->extractInputData($request);
            $validationErrors = $this->validate($inputData);
            
            if (!empty($validationErrors)) {
                return DomainResult::validationError($validationErrors, $this->getOperationName());
            }

            // Create DTO and execute domain logic
            $dto = $this->createDto($request);
            return $this->domain->execute($dto);
            
        } catch (\Exception $e) {
            return DomainResult::failure(
                ['exception' => $e->getMessage()],
                ['trace' => $e->getTraceAsString()],
                $this->getOperationName()
            );
        }
    }

    /**
     * Default validation using validation rules
     */
    public function validate(array $data): array
    {
        $rules = $this->getValidationRules();
        if (empty($rules)) {
            return [];
        }

        return $this->validator->validate($data, $rules);
    }

    /**
     * Extract input data from request
     */
    protected function extractInputData(Request $request): array
    {
        return array_merge(
            $request->get() ?? [],
            $request->post() ?? [],
            $request->input('json') ?? []
        );
    }

    /**
     * Get validation rules for this action
     * Override in child classes to define specific rules
     */
    protected function getValidationRules(): array
    {
        return [];
    }

    /**
     * Get operation name for logging/debugging
     */
    protected function getOperationName(): string
    {
        return static::class;
    }

    /**
     * Child classes must implement DTO creation
     */
    abstract public function createDto(Request $request): object;
}