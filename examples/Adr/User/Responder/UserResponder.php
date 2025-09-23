<?php
// examples/Adr/User/Responder/UserResponder.php
namespace Examples\Adr\User\Responder;

use Core\Adr\AbstractResponder;
use Core\Http\Request;
use Core\Http\Response;

/**
 * User Responder - Converts Domain results to HTTP responses
 */
class UserResponder extends AbstractResponder
{
    /**
     * Handle HTML responses for user operations
     */
    protected function htmlResponse(array $data, Request $request): Response
    {
        $viewPath = $this->getViewPath($request);
        $content = $this->renderView($viewPath, $data);
        
        return new Response($content);
    }

    /**
     * Handle HTML error responses
     */
    protected function htmlErrorResponse(array $data, Request $request): Response
    {
        $viewPath = 'error/user_error';
        $content = $this->renderView($viewPath, $data);
        
        return new Response($content, $this->getErrorStatusCode($data['errors']));
    }

    /**
     * Handle HTML validation error responses
     */
    protected function htmlValidationErrorResponse(array $validationErrors, Request $request): Response
    {
        $viewPath = 'user/create'; // Return to form with errors
        $content = $this->renderView($viewPath, [
            'errors' => $validationErrors,
            'old_input' => $request->all()
        ]);
        
        return new Response($content, 422);
    }

    /**
     * Get appropriate view path based on request
     */
    private function getViewPath(Request $request): string
    {
        $uri = $request->uri();
        
        if (str_contains($uri, '/create')) {
            return 'user/created';
        }
        
        if (str_contains($uri, '/edit')) {
            return 'user/updated';
        }
        
        return 'user/index';
    }

    /**
     * Render view with data (simplified - integrate with your view system)
     */
    private function renderView(string $viewPath, array $data): string
    {
        // This would integrate with your existing view system
        // For now, return a simple response
        extract($data);
        
        ob_start();
        include $this->getViewFile($viewPath);
        return ob_get_clean();
    }

    /**
     * Get view file path
     */
    private function getViewFile(string $viewPath): string
    {
        return __DIR__ . "/../../views/{$viewPath}.phtml";
    }

    /**
     * Override success status code for user creation
     */
    protected function getSuccessStatusCode($data): int
    {
        // Return 201 for creation operations
        if (isset($data['user']) && !isset($data['user']['id'])) {
            return 201;
        }
        
        return 200;
    }
}