<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;

/**
 * Base Controller
 * 
 * All controllers extend this class
 */
abstract class Controller
{
    /**
     * Return JSON response
     */
    protected function json($data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
    
    /**
     * Return view response
     */
    protected function view(string $template, array $data = []): Response
    {
        return view($template, $data);
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
    
    /**
     * Validate request data
     */
    protected function validate(Request $request, array $rules, array $messages = []): array
    {
        return validate($request->all(), $rules, $messages);
    }
    
    /**
     * Get authenticated user
     */
    protected function user()
    {
        return auth()->user();
    }
}
