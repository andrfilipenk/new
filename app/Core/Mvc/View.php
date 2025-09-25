<?php
// app/Core/Mvc/View.php (modified)
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Exception;

class View implements ViewInterface
{
    use Injectable, EventAware;

    protected string $templatePath;
    protected string $layout = 'default';
    protected array $vars = [];
    protected array $sections = [];
    protected bool $layoutEnabled = true;
    protected ?string $activeSection = null;
    protected array $helpers = []; // New: array of helper functions

    public function __construct($config = [])
    {
        $templatePath = $config['path'] ?? 'views/';
        $layout = $config['layout'] ?? 'default';
        $this->setLayout($layout);
        $this->templatePath = rtrim($templatePath, '/\\') . DIRECTORY_SEPARATOR;
    }

    public function setTemplatePath($path)
    {
        $this->templatePath = $path;
        return $this;
    }

    public function render(string $template, array $data = []): string
    {
        $this->fireEvent('view:beforeRender', $this);
        $this->vars = array_merge($this->vars, $data);
        $templateFile = $this->findTemplate($template);
        $content = $this->capture($templateFile, $this->vars);
        if ($this->layoutEnabled && $this->layout) {
            $this->sections['content'] = $content;
            $layoutFile = $this->findTemplate('layouts' . DIRECTORY_SEPARATOR . $this->layout);
            $content = $this->capture($layoutFile, $this->vars);
        }
        $this->fireEvent('view:afterRender', [$this, [ 'output' => $content ] ]);
        return $content;
    }

    public function partial(string $template, array $data = []): string
    {
        $templateFile = $this->findTemplate($template);
        return $this->capture($templateFile, array_merge($this->vars, $data));
    }

    protected function capture(string $templateFile, array $data): string
    {
        $view = $this; // For binding
        $capture = function () use ($templateFile, $data, $view) {
            extract($data, EXTR_SKIP);
            ob_start();
            try {
                include $templateFile;
            } catch (Exception $e) {
                ob_end_clean();
                throw $e;
            }
            return ob_get_clean();
        };
        // Bind $this to the View instance in the closure
        return $capture->call($view);
    }

    protected function findTemplate(string $template): string
    {
        $file = $this->templatePath . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.phtml';
        if (!file_exists($file)) {
            throw new Exception("Template '{$template}' not found at: {$file}");
        }
        return $file;
    }

    public function startSection(string $name): void
    {
        $this->activeSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->activeSection === null) {
            throw new Exception("Cannot end a section without starting one.");
        }
        $this->sections[$this->activeSection] = ob_get_clean();
        $this->activeSection = null;
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function disableLayout(): void
    {
        $this->layoutEnabled = false;
    }

    public function enableLayout(): void
    {
        $this->layoutEnabled = true;
    }

    public function setVar(string $name, $value): void
    {
        $this->vars[$name] = $value;
    }

    public function setVars(array $data): void
    {
        $this->vars = array_merge($this->vars, $data);
    }

    public function getVar(string $name)
    {
        return $this->vars[$name] ?? null;
    }

    // New: Register a helper function
    public function registerHelper(string $name, callable $helper): void
    {
        $this->helpers[$name] = $helper;
    }

    // New: Magic call for helpers and existing methods
    public function __call(string $name, array $arguments)
    {
        if (isset($this->helpers[$name])) {
            return call_user_func_array($this->helpers[$name], $arguments);
        }
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }
        throw new Exception("Method or helper '{$name}' not found in View.");
    }
}