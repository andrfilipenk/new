<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;

/**
 * Middleware Pipeline
 * 
 * Executes middleware in order with onion-style wrapping
 */
class Pipeline
{
    /**
     * @var array Middleware stack
     */
    protected array $middleware = [];
    
    /**
     * @var Request The request being processed
     */
    protected Request $request;
    
    /**
     * @var callable The final handler
     */
    protected $destination;
    
    /**
     * Set the request
     */
    public function send(Request $request): static
    {
        $this->request = $request;
        return $this;
    }
    
    /**
     * Add middleware to the stack
     */
    public function through(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }
    
    /**
     * Set the final destination
     */
    public function then(callable $destination): Response
    {
        $this->destination = $destination;
        
        // Build the middleware chain
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            $this->carry(),
            $this->prepareDestination()
        );
        
        // Execute the pipeline
        return $pipeline($this->request);
    }
    
    /**
     * Prepare the final destination
     */
    protected function prepareDestination(): callable
    {
        return function ($request) {
            return call_user_func($this->destination, $request);
        };
    }
    
    /**
     * Create the middleware carry closure
     */
    protected function carry(): callable
    {
        return function ($stack, $middleware) {
            return function ($request) use ($stack, $middleware) {
                // Resolve middleware instance
                $instance = $this->resolveMiddleware($middleware);
                
                // Execute middleware
                return $instance->handle($request, $stack);
            };
        };
    }
    
    /**
     * Resolve middleware instance
     */
    protected function resolveMiddleware(string|object $middleware): MiddlewareInterface
    {
        if (is_object($middleware)) {
            return $middleware;
        }
        
        // Check if it's a class name
        if (class_exists($middleware)) {
            return new $middleware;
        }
        
        // Check registered middleware aliases
        $aliases = config('middleware.aliases', []);
        
        if (isset($aliases[$middleware])) {
            $class = $aliases[$middleware];
            return new $class;
        }
        
        throw new \Exception("Middleware not found: {$middleware}");
    }
}
