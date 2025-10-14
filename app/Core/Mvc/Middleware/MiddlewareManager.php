<?php
// app/Core/Mvc/Middleware/MiddlewareManager.php
namespace Core\Mvc\Middleware;

use Core\Di\Injectable;

class MiddlewareManager
{
    use Injectable;

    protected array $middleware = [];

    /**
     *
     * @param string $middleware
     * @param array $config
     * @return self
     */
    public function add(string $middleware, array $config = []): self
    {
        $this->middleware[] = [$middleware, $config];
        return $this;
    }

    /**
     *
     * @param string $module
     * @param string $controller
     * @param string $action
     * @return boolean
     */
    public function handle(string $module, string $controller, string $action): bool
    {
        foreach ($this->middleware as [$middleware, $config]) {
            if ($this->shouldSkip("$module.$controller.$action", $config)) continue;
            $instance = new $middleware();
            if (!$instance->handle($this->getDI(), $module, $controller, $action)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param string $path
     * @param array $config
     * @return boolean
     */
    private function shouldSkip(string $path, array $config): bool
    {
        $only = $config['only'] ?? [];
        $except = $config['except'] ?? [];
        return ($only && !$this->matches($path, $only)) || $this->matches($path, $except);
    }

    /**
     *
     * @param string $path
     * @param array $patterns
     * @return boolean
     */
    private function matches(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match('/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/', $path)) {
                return true;
            }
        }
        return false;
    }
}