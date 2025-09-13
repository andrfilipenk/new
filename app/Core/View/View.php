<?php
// app/Core/View/View.php
namespace Core\View;

use Core\Di\Injectable;
use Core\Events\EventAware;

class View implements ViewInterface
{
    use Injectable, EventAware;

    protected $templatePath;
    protected $layout = 'default';
    protected $layoutEnabled = true;
    protected $vars = [];
    protected $content;

    public function __construct(string $templatePath)
    {
        $this->templatePath = rtrim($templatePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function render(string $template, array $data = []): string
    {
        // Merge passed data with stored variables
        $renderData = array_merge($this->vars, $data);
        
        // Trigger beforeRender event
        $event = $this->fireEvent('view:beforeRender', $this, [
            'template' => $template,
            'data' => $renderData
        ]);
        
        if ($event->isPropagationStopped()) {
            return $event->getData() ?? '';
        }
        
        // Extract variables for the template
        extract($renderData, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        try {
            // Include the template file
            include $this->templatePath . $template . '.phtml';
            
            // Get the template content
            $this->content = ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        
        // Apply layout if enabled
        if ($this->layoutEnabled) {
            $output = $this->applyLayout($this->content);
        } else {
            $output = $this->content;
        }
        
        // Trigger afterRender event
        $this->fireEvent('view:afterRender', $this, [
            'output' => $output
        ]);
        
        return $output;
    }

    public function partial(string $template, array $data = []): string
    {
        // Store current layout state
        $layoutEnabled = $this->layoutEnabled;
        
        // Disable layout for partials
        $this->disableLayout();
        
        // Render the partial
        $output = $this->render($template, $data);
        
        // Restore layout state
        if ($layoutEnabled) {
            $this->enableLayout();
        }
        
        return $output;
    }

    protected function applyLayout(string $content): string
    {
        // Extract variables for the layout
        extract($this->vars, EXTR_SKIP);
        
        // Store content for use in layout
        $viewContent = $content;
        
        // Start output buffering
        ob_start();
        
        try {
            // Include the layout file
            include $this->templatePath . 'layouts' . DIRECTORY_SEPARATOR . $this->layout . '.phtml';
            
            // Get the layout content
            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
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

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}