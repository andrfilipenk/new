<?php
// app/Core/Mvc/ResourceRouter.php
namespace Core\Mvc;

/**
 * Resource router for automatic CRUD route generation
 * Following super-senior PHP practices with convention over configuration
 */
class ResourceRouter
{
    protected array $resources = [];
    protected array $defaultActions = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
    
    /**
     * Register a resource with automatic CRUD routes
     */
    public function resource(string $name, string $controller, array $options = []): array
    {
        $prefix     = $options['prefix'] ?? '';
        $only       = $options['only'] ?? $this->defaultActions;
        $except     = $options['except'] ?? [];
        $as         = $options['as'] ?? '';
        // Filter actions
        $actions    = array_diff($only, $except);
        $routes     = [];
        foreach ($actions as $action) {
            $route = $this->generateRoute($name, $action, $controller, $prefix, $as);
            if ($route) {
                $routes[] = $route;
            }
        }
        $this->resources[$name] = $routes;
        return $routes;
    }
    
    /**
     * Register multiple resources at once
     */
    public function resources(array $resources): array
    {
        $allRoutes = [];
        foreach ($resources as $name => $config) {
            if (is_string($config)) {
                // Simple case: name => controller
                $routes = $this->resource($name, $config);
            } else {
                // Complex case: name => [controller, options]
                $controller = $config[0];
                $options = $config[1] ?? [];
                $routes = $this->resource($name, $controller, $options);
            }
            $allRoutes = array_merge($allRoutes, $routes);
        }
        return $allRoutes;
    }
    
    /**
     * Generate individual route for an action
     */
    protected function generateRoute(string $resource, string $action, string $controller, string $prefix, string $as): ?array
    {
        $routeMap = [
            'index' => [
                'pattern' => "/{$prefix}{$resource}",
                'method' => 'GET',
                'action' => 'index'
            ],
            'create' => [
                'pattern' => "/{$prefix}{$resource}/create",
                'method' => 'GET',
                'action' => 'create'
            ],
            'store' => [
                'pattern' => "/{$prefix}{$resource}",
                'method' => 'POST',
                'action' => 'store'
            ],
            'show' => [
                'pattern' => "/{$prefix}{$resource}/{id}",
                'method' => 'GET',
                'action' => 'show'
            ],
            'edit' => [
                'pattern' => "/{$prefix}{$resource}/{id}/edit",
                'method' => 'GET',
                'action' => 'edit'
            ],
            'update' => [
                'pattern' => "/{$prefix}{$resource}/{id}",
                'method' => ['PUT', 'PATCH'],
                'action' => 'update'
            ],
            'destroy' => [
                'pattern' => "/{$prefix}{$resource}/{id}",
                'method' => 'DELETE',
                'action' => 'destroy'
            ]
        ];
        if (!isset($routeMap[$action])) {
            return null;
        }
        $route  = $routeMap[$action];
        $name   = $as ? "{$as}.{$action}" : "{$resource}.{$action}";
        return [
            'pattern'       => $route['pattern'],
            'controller'    => $controller,
            'action'        => $route['action'],
            'method'        => $route['method'],
            'name'          => $name
        ];
    }
    
    /**
     * Get all registered resources
     */
    public function getResources(): array
    {
        return $this->resources;
    }
    
    /**
     * Get routes for a specific resource
     */
    public function getResourceRoutes(string $resource): array
    {
        return $this->resources[$resource] ?? [];
    }
    
    /**
     * Convert resource routes to router-compatible format
     */
    public function toRouterConfig(): array
    {
        $config = [];
        foreach ($this->resources as $resourceName => $routes) {
            foreach ($routes as $route) {
                $pattern = $route['pattern'];
                // Handle multiple HTTP methods
                $methods = is_array($route['method']) ? $route['method'] : [$route['method']];
                foreach ($methods as $method) {
                    $key = strtoupper($method) . ' ' . $pattern;
                    $config[$key] = [
                        'controller'    => $route['controller'],
                        'action'        => $route['action'],
                        'method'        => strtoupper($method)
                    ];
                }
            }
        }
        return $config;
    }
    
    /**
     * Helper method to generate API resource routes
     */
    public function apiResource(string $name, string $controller, array $options = []): array
    {
        // API resources typically don't need create/edit forms
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);
        return $this->resource($name, $controller, $options);
    }
    
    /**
     * Helper method to generate nested resources
     */
    public function nestedResource(string $parent, string $child, string $controller, array $options = []): array
    {
        $prefix = $options['prefix'] ?? '';
        $options['prefix'] = "{$prefix}{$parent}/{parent_id}/";
        return $this->resource($child, $controller, $options);
    }
}