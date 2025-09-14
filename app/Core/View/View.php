<?php
// app/Core/View/View.php
namespace Core\View;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Exception;

class View implements ViewInterface
{
    use Injectable, EventAware;

    protected $templatePath;
    protected $cachePath;
    protected $layout = 'default';
    protected $layoutEnabled = true;
    protected $vars = [];
    protected $sections = [];
    protected $activeSection;

    public function __construct(string $templatePath, ?string $cachePath = null)
    {
        $this->templatePath = rtrim($templatePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($cachePath) {
            $this->cachePath = rtrim($cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (!is_dir($this->cachePath)) {
                mkdir($this->cachePath, 0755, true);
            }
        }
    }

    public function render(string $template, array $data = []): string
    {
        $this->vars = array_merge($this->vars, $data);
        $templateFile = $this->templatePath . $template . '.phtml';
        
        if ($this->cachePath) {
            $cachedFile = $this->cachePath . str_replace(['/', '\\'], '_', $template) . '.php';
            if (!file_exists($cachedFile) || filemtime($templateFile) > filemtime($cachedFile)) {
                $content = file_get_contents($templateFile);
                // In a real scenario, you might do more complex parsing here.
                // For now, we just copy it.
                file_put_contents($cachedFile, $content);
            }
            $templateFile = $cachedFile;
        }

        $this->fireEvent('view:beforeRender', $this);

        $content = $this->capture($templateFile, $this->vars);

        if ($this->layoutEnabled && $this->layout) {
            $this->sections['content'] = $content;
            $layoutFile = $this->templatePath . 'layouts' . DIRECTORY_SEPARATOR . $this->layout . '.phtml';
            $output = $this->capture($layoutFile, $this->vars);
        } else {
            $output = $content;
        }

        $this->fireEvent('view:afterRender', $this, ['output' => $output]);
        
        return $output;
    }

    protected function capture(string $templateFile, array $data): string
    {
        // Make the view instance itself available in the template
        $data['view'] = $this;
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

    public function partial(string $template, array $data = []): string
    {
        $originalLayoutState = $this->layoutEnabled;
        $this->layoutEnabled = false;
        $output = $this->render($template, $data);
        $this->layoutEnabled = $originalLayoutState;
        return $output;
    }

    public function startSection(string $name): void
    {
        $this->activeSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->activeSection) {
            $this->sections[$this->activeSection] = ob_get_clean();
            $this->activeSection = null;
        }
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

// example usage in a template:
// <!--?php $this->startSection('header'); ?-->
// <!--?php $this->endSection(); ?-->
// <!--?= $this->yield('header', '<h1>Default Header</h1>'); ?-->
// <!--?= $this->yield('content'); ?--> <!-- Main content section -->