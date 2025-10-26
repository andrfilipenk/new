<?php

namespace Core\View;

use Core\Response;

/**
 * View Engine
 * 
 * Simple template engine with layouts, sections, and auto-escaping
 */
class ViewEngine
{
    /**
     * @var string Base directory for views
     */
    protected static string $viewPath;
    
    /**
     * @var array Data shared across all views
     */
    protected static array $sharedData = [];
    
    /**
     * @var string Current layout file
     */
    protected static ?string $layout = null;
    
    /**
     * @var array Sections storage
     */
    protected static array $sections = [];
    
    /**
     * @var string Current section being captured
     */
    protected static ?string $currentSection = null;
    
    /**
     * @var array Section content buffer
     */
    protected static array $sectionBuffer = [];
    
    /**
     * Initialize the view engine
     */
    public static function init(): void
    {
        static::$viewPath = dirname(__DIR__, 2) . '/views';
    }
    
    /**
     * Set the views directory
     */
    public static function setViewPath(string $path): void
    {
        static::$viewPath = $path;
    }
    
    /**
     * Render a view and return Response object
     */
    public static function render(string $view, array $data = []): Response
    {
        $html = static::make($view, $data);
        return Response::html($html);
    }
    
    /**
     * Make a view and return HTML string
     */
    public static function make(string $view, array $data = []): string
    {
        // Reset state
        static::$layout = null;
        static::$sections = [];
        static::$currentSection = null;
        static::$sectionBuffer = [];
        
        // Merge data with shared data
        $data = array_merge(static::$sharedData, $data);
        
        // Get view path
        $viewPath = static::getViewPath($view);
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view} (Path: {$viewPath})");
        }
        
        // Render view
        $content = static::renderFile($viewPath, $data);
        
        // If layout is set, render within layout
        if (static::$layout) {
            $layoutPath = static::getViewPath(static::$layout);
            
            if (!file_exists($layoutPath)) {
                throw new \Exception("Layout not found: " . static::$layout);
            }
            
            // Store content section
            static::$sections['content'] = $content;
            
            // Render layout
            $content = static::renderFile($layoutPath, $data);
        }
        
        return $content;
    }
    
    /**
     * Share data across all views
     */
    public static function share(string|array $key, $value = null): void
    {
        if (is_array($key)) {
            static::$sharedData = array_merge(static::$sharedData, $key);
        } else {
            static::$sharedData[$key] = $value;
        }
    }
    
    /**
     * Check if a view exists
     */
    public static function exists(string $view): bool
    {
        return file_exists(static::getViewPath($view));
    }
    
    /**
     * Set the layout for the current view
     */
    public static function extend(string $layout): void
    {
        static::$layout = $layout;
    }
    
    /**
     * Start a section
     */
    public static function section(string $name): void
    {
        static::$currentSection = $name;
        ob_start();
    }
    
    /**
     * End a section
     */
    public static function endSection(): void
    {
        if (static::$currentSection === null) {
            throw new \Exception("No section started");
        }
        
        $content = ob_get_clean();
        static::$sections[static::$currentSection] = $content;
        static::$currentSection = null;
    }
    
    /**
     * Output a section
     */
    public static function yield(string $name, string $default = ''): string
    {
        return static::$sections[$name] ?? $default;
    }
    
    /**
     * Include a partial view
     */
    public static function include(string $view, array $data = []): void
    {
        $viewPath = static::getViewPath($view);
        
        if (!file_exists($viewPath)) {
            throw new \Exception("Partial view not found: {$view}");
        }
        
        echo static::renderFile($viewPath, $data);
    }
    
    /**
     * Escape HTML output
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
    
    /**
     * Output escaped value (shorthand)
     */
    public static function e(string $value): string
    {
        return static::escape($value);
    }
    
    /**
     * Get full path to view file
     */
    protected static function getViewPath(string $view): string
    {
        // Replace dots with directory separators
        $path = str_replace('.', '/', $view);
        
        // Add .php extension if not present
        if (substr($path, -4) !== '.php') {
            $path .= '.php';
        }
        
        return static::$viewPath . '/' . $path;
    }
    
    /**
     * Render a view file
     */
    protected static function renderFile(string $viewPath, array $data): string
    {
        // Extract data to make variables available in the view
        extract($data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        try {
            // Include the view file
            require $viewPath;
            
            // Get and clean buffer
            return ob_get_clean();
        } catch (\Exception $e) {
            // Clean buffer on error
            ob_end_clean();
            throw $e;
        }
    }
}

// Initialize
ViewEngine::init();
