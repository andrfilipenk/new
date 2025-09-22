<?php
// app/Core/Mvc/View.php
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

    public function __construct(string $templatePath)
    {
        $this->templatePath = rtrim($templatePath, '/\\') . DIRECTORY_SEPARATOR;
    }

    public function render(string $template, array $data = []): string
    {
        $this->fireEvent('view:beforeRender', $this);
        $this->vars     = array_merge($this->vars, $data);
        $templateFile   = $this->findTemplate($template);
        $content        = $this->capture($templateFile, $this->vars);
        if ($this->layoutEnabled && $this->layout) {
            $this->sections['content'] = $content;
            $layoutFile = $this->findTemplate('layouts' . DIRECTORY_SEPARATOR . $this->layout);
            $content    = $this->capture($layoutFile, $this->vars);
        }
        $this->fireEvent('view:afterRender', [$this,
            [
                'output' => $content
            ]
        ]);
        return $content;
    }

    public function partial(string $template, array $data = []): string
    {
        $templateFile = $this->findTemplate($template);
        return $this->capture($templateFile, array_merge($this->vars, $data));
    }

    protected function capture(string $templateFile, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $templateFile;
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
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
}