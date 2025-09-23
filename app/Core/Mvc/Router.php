<?php
// app/Core/Mvc/Router.php
namespace Core\Mvc;

class Router
{
    protected $routes = [];
    protected $currentRoute;
    
    public function add(string $pattern, array $config): void
    {
        $regex = $this->patternToRegex($pattern);
        $this->routes[] = [
            'pattern'   => $pattern,
            'regex'     => $regex,
            'config'    => $config
        ];
    }
    
    public function match(string $uri, string $method): ?array
    {
        $uri = trim($uri, '/');
        foreach ($this->routes as $route) {
            $methodMatch = !isset($route['config']['method']) || strcasecmp($route['config']['method'], $method) === 0;
            if ($methodMatch && preg_match($route['regex'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->currentRoute = $route;
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
        $regex = preg_quote(trim($pattern, '/'), '#');
        $regex = str_replace('\*', '.*', $regex);
        $regex = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '(?P<$1>[^/]+)', $regex);
        return '#^' . $regex . '$#';
    }
    
    protected function buildResult(array $config, array $params): array
    {
        $result = $config;
        foreach (['module', 'controller', 'action'] as $key) {
            if (isset($result[$key])) {
                $result[$key] = $this->replacePlaceholders($result[$key], $params);
            }
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }
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