<?php
// app/Core/Router/Router.php
namespace Core\Mvc;

class Router
{
    protected $routes = [];
    protected $currentRoute;
    
    public function add(string $pattern, array $config): void
    {
        // Convert pattern to regex
        $regex = $this->patternToRegex($pattern);
        
        $this->routes[] = [
            'pattern' => $pattern,
            'regex' => $regex,
            'config' => $config
        ];
    }
    
    public function match(string $uri): ?array
    {
        $uri = trim($uri, '/');
        
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                // Filter out numeric keys (keep only named captures)
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Store current route
                $this->currentRoute = $route;
                
                // Merge params with config
                return $this->buildResult($route['config'], $params);
            }
        }
        return null;
    }
    
    public function getCurrentRoute(): ?array
    {
        return $this->currentRoute;
    }
    
    protected function patternToRegex(string $pattern): string
    {
        // Escape forward slashes
        $regex = preg_quote(trim($pattern, '/'), '#');
        
        // Handle wildcards
        $regex = str_replace('\*', '.*', $regex);
        
        // Handle named parameters
        $regex = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '(?P<$1>[^/]+)', $regex);
        
        return '#^' . $regex . '$#';
    }
    
    protected function buildResult(array $config, array $params): array
    {
        $result = $config;
        
        // Replace placeholders in controller and action
        foreach (['controller', 'action'] as $key) {
            if (isset($result[$key])) {
                $result[$key] = $this->replacePlaceholders($result[$key], $params);
            }
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }
        
        // Add params to result
        $result['params'] = $params;
        
        return $result;
    }
    
    protected function replacePlaceholders(string $value, array $params): string
    {
        return preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', 
            function($matches) use ($params) {
                return $params[$matches[1]] ?? $matches[0];
            }, 
            $value
        );
    }
}