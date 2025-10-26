<?php

namespace Core;

/**
 * Router
 * 
 * RESTful router with named routes, parameter binding, and route grouping
 */
class Router
{
    protected static array $routes = [];
    protected static array $namedRoutes = [];
    protected static array $groupStack = [];
    protected static ?Route $currentRoute = null;

    /**
     * Register GET route
     */
    public static function get(string $uri, $action, array $options = []): Route
    {
        return self::addRoute('GET', $uri, $action, $options);
    }

    /**
     * Register POST route
     */
    public static function post(string $uri, $action, array $options = []): Route
    {
        return self::addRoute('POST', $uri, $action, $options);
    }

    /**
     * Register PUT route
     */
    public static function put(string $uri, $action, array $options = []): Route
    {
        return self::addRoute('PUT', $uri, $action, $options);
    }

    /**
     * Register PATCH route
     */
    public static function patch(string $uri, $action, array $options = []): Route
    {
        return self::addRoute('PATCH', $uri, $action, $options);
    }

    /**
     * Register DELETE route
     */
    public static function delete(string $uri, $action, array $options = []): Route
    {
        return self::addRoute('DELETE', $uri, $action, $options);
    }

    /**
     * Register OPTIONS route
     */
    public static function options(string $uri, $action, array $options = []): Route
    {
        return self::addRoute('OPTIONS', $uri, $action, $options);
    }

    /**
     * Register route for any method
     */
    public static function any(string $uri, $action): Route
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $route = null;
        
        foreach ($methods as $method) {
            $route = self::addRoute($method, $uri, $action);
        }
        
        return $route;
    }

    /**
     * Register RESTful resource routes
     */
    public static function resource(string $name, string $controller): void
    {
        $name = trim($name, '/');
        
        // Index - GET /resource
        self::get($name, [$controller, 'index'])->name("{$name}.index");
        
        // Create - GET /resource/create
        self::get("{$name}/create", [$controller, 'create'])->name("{$name}.create");
        
        // Store - POST /resource
        self::post($name, [$controller, 'store'])->name("{$name}.store");
        
        // Show - GET /resource/{id}
        self::get("{$name}/{id}", [$controller, 'show'])->name("{$name}.show");
        
        // Edit - GET /resource/{id}/edit
        self::get("{$name}/{id}/edit", [$controller, 'edit'])->name("{$name}.edit");
        
        // Update - PUT/PATCH /resource/{id}
        self::put("{$name}/{id}", [$controller, 'update'])->name("{$name}.update");
        self::patch("{$name}/{id}", [$controller, 'update']);
        
        // Destroy - DELETE /resource/{id}
        self::delete("{$name}/{id}", [$controller, 'destroy'])->name("{$name}.destroy");
    }

    /**
     * Create route group
     */
    public static function group(array $attributes, callable $callback): void
    {
        self::$groupStack[] = $attributes;
        $callback();
        array_pop(self::$groupStack);
    }

    /**
     * Add route
     */
    protected static function addRoute(string $method, string $uri, $action, array $options = []): Route
    {
        $uri = '/' . trim($uri, '/');
        
        // Apply group attributes
        $uri = self::applyGroupPrefix($uri);
        $middleware = self::getGroupMiddleware();
        
        // Merge route-specific middleware
        if (isset($options['middleware'])) {
            $routeMiddleware = is_array($options['middleware']) ? $options['middleware'] : [$options['middleware']];
            $middleware = array_merge($middleware, $routeMiddleware);
        }
        
        $route = new Route($method, $uri, $action);
        $route->middleware($middleware);
        
        if (!isset(self::$routes[$method])) {
            self::$routes[$method] = [];
        }
        
        self::$routes[$method][$uri] = $route;
        
        return $route;
    }

    /**
     * Apply group prefix to URI
     */
    protected static function applyGroupPrefix(string $uri): string
    {
        $prefix = '';
        
        foreach (self::$groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        
        return $prefix . $uri;
    }

    /**
     * Get group middleware
     */
    protected static function getGroupMiddleware(): array
    {
        $middleware = [];
        
        foreach (self::$groupStack as $group) {
            if (isset($group['middleware'])) {
                $groupMiddleware = is_array($group['middleware']) 
                    ? $group['middleware'] 
                    : [$group['middleware']];
                $middleware = array_merge($middleware, $groupMiddleware);
            }
        }
        
        return $middleware;
    }

    /**
     * Dispatch request
     */
    public static function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $request->path();
        
        // Find matching route
        $match = self::findRoute($method, $uri);
        
        if (!$match) {
            // Prepare debug information
            $debug = null;
            if (config('app.debug', false)) {
                $availableRoutes = [];
                if (isset(self::$routes[$method])) {
                    $availableRoutes = array_keys(self::$routes[$method]);
                }
                
                $debug = [
                    'requested_method' => $method,
                    'requested_path' => $uri,
                    'requested_uri' => $request->uri(),
                    'base_path' => $request->basePath(),
                    'registered_routes_count' => count($availableRoutes),
                    'available_routes' => $availableRoutes,
                ];
            }
            
            return Response::notFound('Route not found', $debug);
        }
        
        self::$currentRoute = $match['route'];
        $request->setRouteParams($match['params']);
        
        return $match['route']->execute($request);
    }

    /**
     * Find matching route
     */
    protected static function findRoute(string $method, string $uri): ?array
    {
        if (!isset(self::$routes[$method])) {
            return null;
        }
        
        foreach (self::$routes[$method] as $pattern => $route) {
            $params = self::matchRoute($pattern, $uri, $route);
            
            // matchRoute returns null if no match, or an array (possibly empty) if matched
            if ($params !== null) {
                return [
                    'route' => $route,
                    'params' => $params,
                ];
            }
        }
        
        return null;
    }

    /**
     * Match route pattern against URI
     */
    protected static function matchRoute(string $pattern, string $uri, Route $route): ?array
    {
        // Exact match
        if ($pattern === $uri) {
            return [];
        }
        
        // Convert route pattern to regex
        $regex = self::buildRegex($pattern, $route);
        
        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches); // Remove full match
            return self::extractNamedParameters($pattern, $matches);
        }
        
        return null;
    }

    /**
     * Build regex from route pattern
     */
    protected static function buildRegex(string $pattern, Route $route): string
    {
        $wheres = $route->getWheres();
        
        // Replace parameters with regex
        $regex = preg_replace_callback('/\{(\w+)(\?)?\}/', function($matches) use ($wheres) {
            $param = $matches[1];
            $optional = isset($matches[2]);
            
            $constraint = $wheres[$param] ?? '[^/]+';
            
            if ($optional) {
                return '(' . $constraint . ')?';
            }
            
            return '(' . $constraint . ')';
        }, $pattern);
        
        return '#^' . $regex . '$#';
    }

    /**
     * Extract named parameters
     */
    protected static function extractNamedParameters(string $pattern, array $values): array
    {
        preg_match_all('/\{(\w+)(\?)?\}/', $pattern, $matches);
        $names = $matches[1];
        
        $params = [];
        foreach ($names as $index => $name) {
            if (isset($values[$index])) {
                $params[$name] = $values[$index];
            }
        }
        
        return $params;
    }

    /**
     * Generate URL for named route
     */
    public static function url(string $name, array $params = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new \RuntimeException("Named route [{$name}] not found");
        }
        
        $uri = self::$namedRoutes[$name];
        
        // Replace parameters
        foreach ($params as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
            $uri = str_replace("{{$key}?}", $value, $uri);
        }
        
        // Remove optional parameters
        $uri = preg_replace('/\{(\w+)\?\}/', '', $uri);
        
        return url($uri);
    }

    /**
     * Register named route
     */
    public static function registerNamed(string $name, string $uri): void
    {
        self::$namedRoutes[$name] = $uri;
    }

    /**
     * Get current route
     */
    public static function current(): ?Route
    {
        return self::$currentRoute;
    }

    /**
     * Get current route name
     */
    public static function currentRouteName(): ?string
    {
        return self::$currentRoute?->getName();
    }

    /**
     * Get all routes
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }
}

/**
 * Route Class
 */
class Route
{
    protected string $method;
    protected string $uri;
    protected $action;
    protected array $middleware = [];
    protected ?string $name = null;
    protected array $wheres = [];

    public function __construct(string $method, string $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }

    /**
     * Set route name
     */
    public function name(string $name): static
    {
        $this->name = $name;
        Router::registerNamed($name, $this->uri);
        return $this;
    }

    /**
     * Add middleware
     */
    public function middleware($middleware): static
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        
        return $this;
    }

    /**
     * Add parameter constraint
     */
    public function where(string $param, string $pattern): static
    {
        $this->wheres[$param] = $pattern;
        return $this;
    }

    /**
     * Execute route action
     */
    public function execute(Request $request): Response
    {
        // Execute middleware using Pipeline
        $pipeline = new \Core\Middleware\Pipeline();
        
        return $pipeline
            ->send($request)
            ->through($this->middleware)
            ->then(function ($request) {
                // Execute action
                if (is_callable($this->action)) {
                    $result = call_user_func($this->action, $request);
                } elseif (is_array($this->action)) {
                    [$controller, $method] = $this->action;
                    $instance = new $controller();
                    $result = $instance->$method($request);
                } elseif (is_string($this->action) && strpos($this->action, '@') !== false) {
                    // Handle Controller@method string format
                    [$controller, $method] = explode('@', $this->action);
                    $instance = new $controller();
                    $result = $instance->$method($request);
                } else {
                    throw new \RuntimeException("Invalid route action");
                }
                
                // Convert result to Response
                if ($result instanceof Response) {
                    return $result;
                }
                
                if (is_array($result)) {
                    return Response::json($result);
                }
                
                return Response::html((string) $result);
            });
    }

    /**
     * Get route name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get parameter constraints
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * Get URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get method
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
