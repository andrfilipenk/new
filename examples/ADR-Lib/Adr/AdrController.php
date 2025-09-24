<?php
// app/Core\Adr\AdrController.php
namespace Core\Adr;

use Core\Mvc\Controller;
use Core\Http\Request;
use Core\Http\Response;

/**
 * ADR-aware Controller
 * 
 * Integrates ADR pattern with existing MVC structure
 * Provides clean separation between Action, Domain, and Responder
 */
class AdrController extends Controller
{
    protected ActionInterface $action;
    protected ResponderInterface $responder;

    /**
     * Execute ADR flow
     */
    protected function executeAdr(
        ActionInterface $action,
        ResponderInterface $responder,
        Request $request = null
    ): Response {
        $request = $request ?? $this->getRequest();
        
        // Execute Action (handles HTTP input, calls Domain)
        $domainResult = $action->execute($request);
        
        // Execute Responder (converts Domain result to HTTP response)
        return $responder->respond($domainResult, $request);
    }

    /**
     * Set action for this controller
     */
    protected function setAction(ActionInterface $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Set responder for this controller
     */
    protected function setResponder(ResponderInterface $responder): self
    {
        $this->responder = $responder;
        return $this;
    }

    /**
     * Get configured action
     */
    protected function getAction(): ?ActionInterface
    {
        return $this->action ?? null;
    }

    /**
     * Get configured responder
     */
    protected function getResponder(): ?ResponderInterface
    {
        return $this->responder ?? null;
    }

    /**
     * Create action instance via DI
     */
    protected function createAction(string $actionClass): ActionInterface
    {
        return $this->getDI()->get($actionClass);
    }

    /**
     * Create responder instance via DI
     */
    protected function createResponder(string $responderClass): ResponderInterface
    {
        return $this->getDI()->get($responderClass);
    }

    /**
     * Factory method for quick ADR execution
     */
    protected function adr(
        string $actionClass,
        string $responderClass,
        Request $request = null
    ): Response {
        $action = $this->createAction($actionClass);
        $responder = $this->createResponder($responderClass);
        
        return $this->executeAdr($action, $responder, $request);
    }

    /**
     * Auto-wire ADR components based on naming convention
     * 
     * For action 'createUser', looks for:
     * - Action: CreateUserAction
     * - Responder: UserResponder
     */
    protected function autoAdr(string $operationName, Request $request = null): Response
    {
        $actionClass = $this->getActionClassName($operationName);
        $responderClass = $this->getResponderClassName($operationName);
        
        return $this->adr($actionClass, $responderClass, $request);
    }

    /**
     * Get action class name by convention
     */
    protected function getActionClassName(string $operationName): string
    {
        $namespace = $this->getActionNamespace();
        $className = ucfirst($operationName) . 'Action';
        
        return $namespace . '\\' . $className;
    }

    /**
     * Get responder class name by convention
     */
    protected function getResponderClassName(string $operationName): string
    {
        $namespace = $this->getResponderNamespace();
        
        // Extract entity name from operation (e.g., 'createUser' -> 'User')
        $entityName = $this->extractEntityName($operationName);
        $className = ucfirst($entityName) . 'Responder';
        
        return $namespace . '\\' . $className;
    }

    /**
     * Get action namespace (override in child classes)
     */
    protected function getActionNamespace(): string
    {
        $dispatcher = $this->getDI()->get('dispatcher');
        $module = ucfirst($dispatcher->getModuleName());
        
        return "Module\\{$module}\\Action";
    }

    /**
     * Get responder namespace (override in child classes)
     */
    protected function getResponderNamespace(): string
    {
        $dispatcher = $this->getDI()->get('dispatcher');
        $module = ucfirst($dispatcher->getModuleName());
        
        return "Module\\{$module}\\Responder";
    }

    /**
     * Extract entity name from operation name
     */
    protected function extractEntityName(string $operationName): string
    {
        // Remove common prefixes
        $prefixes = ['create', 'update', 'delete', 'get', 'list', 'find', 'show'];
        
        foreach ($prefixes as $prefix) {
            if (str_starts_with(strtolower($operationName), $prefix)) {
                return substr($operationName, strlen($prefix));
            }
        }
        
        return $operationName;
    }
}