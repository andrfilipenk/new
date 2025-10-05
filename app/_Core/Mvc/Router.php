<?php
// app/_Core/Mvc/Router.php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Core\Mvc\Router\Route;

class Router
{
    use Injectable, EventAware;

    /**
     * Holds routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Register route
     *
     * @param string $pattern
     * @param array $config
     * @return Route
     */
    public function add(string $pattern, array $config)
    {
        $regex = $this->patternToRegex($pattern);
        $method = $config['method'] ?? ['GET'];
        $config['method'] = is_string($method) ? [$method] : $method;
        $config['pattern'] = $pattern;
        $config['regex'] = $regex;
        $route = Route::fromArray($config);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * Register many routes
     *
     * @param array $routes
     * @return $this
     */
    public function addRoutes(array $routes)
    {
        foreach ($routes as $pattern => $config) {
            $this->add($pattern, $config);
        }
        return $this;
    }

    /**
     * Summary of resetRoutes
     * 
     * @return static
     */
    public function resetRoutes()
    {
        $this->routes = [];
        return $this;
    }

    /**
     * Summary of group match
     * 
     * @param string $uri
     * @param string $method
     * @param array $routes
     * @return array|false
     */
    public function matchRouteGroup(string $uri, string $method, array $routes)
    {
        return $this
            ->resetRoutes()
            ->addRoutes($routes)
            ->match($uri, $method);
    }

    /**
     * Match route
     *
     * @param string $uri
     * @param string $method
     * @return array|false
     */
    public function match(string $uri, string $method)
    {
        $uri = trim($uri, '/');
        foreach ($this->routes as $route) {
            $this->fireEvent('router.checkRoute', $route);
            if (!$route->isMethod($method)) {
                continue;
            }
            if (!preg_match($route->regex, $uri, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $result = $this->buildResult($route, $params);
            $route->setMatched();
            $route->setData($result);
            $this->fireEvent('router.matchedRoute', $route);
            return $route->getResult();
        }
        return false;
    }

    /**
     * Convert pattern
     *
     * @param string $pattern
     * @return string
     */
    protected function patternToRegex(string $pattern): string
    {
        $regex = preg_quote(trim($pattern, '/'), '#');
        $regex = str_replace('\*', '.*', $regex);
        $regex = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '(?P<$1>[^/]+)', $regex);
        return '#^' . $regex . '$#';
    }

    /**
     * Clean up route result
     *
     * @param Route $route
     * @param array $params
     * @return array
     */
    protected function buildResult(Route $route, array $params): array
    {
        $result = [];
        foreach (['module', 'controller', 'action'] as $key) {
            if (isset($route->$key)) {
                $result[$key] = $this->replacePlaceholders($route->$key, $params);
            }
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }
        $result['params'] = $params;
        return $result;
    }
    
    /**
     * Replace placeholders
     *
     * @param string $value
     * @param array $params
     * @return string
     */
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