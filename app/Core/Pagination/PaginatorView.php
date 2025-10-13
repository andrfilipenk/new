<?php
// app/Core/Pagination/PaginatorView.php
namespace Core\Pagination;

class PaginatorView
{
    protected Paginator $paginator;
    protected array $options;

    public function __construct(Paginator $paginator, array $options = [])
    {
        $this->paginator = $paginator;
        $this->options = array_merge([
            'view' => 'default',
            'previous_text' => '&laquo; Previous',
            'next_text' => 'Next &raquo;',
            'show_numbers' => true,
            'show_first_last' => true,
            'show_info' => true,
            'on_each_side' => 3,
            'css_classes' => [
                'wrapper' => 'pagination-wrapper',
                'list' => 'pagination',
                'item' => 'page-item',
                'link' => 'page-link',
                'active' => 'active',
                'disabled' => 'disabled',
                'info' => 'pagination-info'
            ]
        ], $options);
    }

    /**
     * Render the pagination view
     */
    public function render(): string
    {
        if (!$this->paginator->hasPages()) {
            return '';
        }

        switch ($this->options['view']) {
            case 'simple':
                return $this->renderSimple();
            case 'compact':
                return $this->renderCompact();
            case 'bootstrap':
                return $this->renderBootstrap();
            default:
                return $this->renderDefault();
        }
    }

    /**
     * Render default pagination
     */
    protected function renderDefault(): string
    {
        $html = '<div class="' . $this->options['css_classes']['wrapper'] . '">';
        
        if ($this->options['show_info']) {
            $html .= $this->renderInfo();
        }
        
        $html .= '<nav>';
        $html .= '<ul class="' . $this->options['css_classes']['list'] . '">';
        
        // Previous page link
        $html .= $this->renderPreviousLink();
        
        if ($this->options['show_numbers']) {
            // First page link
            if ($this->options['show_first_last']) {
                $html .= $this->renderFirstLink();
            }
            
            // Page number links
            $html .= $this->renderPageLinks();
            
            // Last page link
            if ($this->options['show_first_last']) {
                $html .= $this->renderLastLink();
            }
        }
        
        // Next page link
        $html .= $this->renderNextLink();
        
        $html .= '</ul>';
        $html .= '</nav>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render simple pagination (only prev/next)
     */
    protected function renderSimple(): string
    {
        $html = '<div class="' . $this->options['css_classes']['wrapper'] . '">';
        $html .= '<nav>';
        $html .= '<ul class="' . $this->options['css_classes']['list'] . ' pagination-simple">';
        
        $html .= $this->renderPreviousLink();
        $html .= $this->renderNextLink();
        
        $html .= '</ul>';
        $html .= '</nav>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render compact pagination (numbers only, no text)
     */
    protected function renderCompact(): string
    {
        $html = '<div class="' . $this->options['css_classes']['wrapper'] . '">';
        $html .= '<nav>';
        $html .= '<ul class="' . $this->options['css_classes']['list'] . ' pagination-compact">';
        
        $html .= $this->renderPageLinks();
        
        $html .= '</ul>';
        $html .= '</nav>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render Bootstrap-style pagination
     */
    protected function renderBootstrap(): string
    {
        $this->options['css_classes'] = array_merge($this->options['css_classes'], [
            'wrapper' => 'd-flex justify-content-between align-items-center',
            'list' => 'pagination mb-0',
            'item' => 'page-item',
            'link' => 'page-link',
            'active' => 'active',
            'disabled' => 'disabled',
            'info' => 'text-muted'
        ]);
        
        return $this->renderDefault();
    }

    /**
     * Render pagination info
     */
    protected function renderInfo(): string
    {
        $from = $this->paginator->firstItem();
        $to = $this->paginator->lastItem();
        $total = $this->paginator->total();
        
        return '<div class="' . $this->options['css_classes']['info'] . '">' .
               "Showing {$from} to {$to} of {$total} results" .
               '</div>';
    }

    /**
     * Render previous page link
     */
    protected function renderPreviousLink(): string
    {
        if ($this->paginator->hasPreviousPage()) {
            $url = $this->paginator->previousPageUrl();
            return '<li class="' . $this->options['css_classes']['item'] . '">' .
                   '<a href="' . htmlspecialchars($url) . '" class="' . $this->options['css_classes']['link'] . '">' .
                   $this->options['previous_text'] .
                   '</a></li>';
        }
        
        return '<li class="' . $this->options['css_classes']['item'] . ' ' . $this->options['css_classes']['disabled'] . '">' .
               '<span class="' . $this->options['css_classes']['link'] . '">' .
               $this->options['previous_text'] .
               '</span></li>';
    }

    /**
     * Render next page link
     */
    protected function renderNextLink(): string
    {
        if ($this->paginator->hasNextPage()) {
            $url = $this->paginator->nextPageUrl();
            return '<li class="' . $this->options['css_classes']['item'] . '">' .
                   '<a href="' . htmlspecialchars($url) . '" class="' . $this->options['css_classes']['link'] . '">' .
                   $this->options['next_text'] .
                   '</a></li>';
        }
        
        return '<li class="' . $this->options['css_classes']['item'] . ' ' . $this->options['css_classes']['disabled'] . '">' .
               '<span class="' . $this->options['css_classes']['link'] . '">' .
               $this->options['next_text'] .
               '</span></li>';
    }

    /**
     * Render first page link
     */
    protected function renderFirstLink(): string
    {
        if ($this->paginator->currentPage() > ($this->options['on_each_side'] + 1)) {
            $url = $this->paginator->firstPageUrl();
            return '<li class="' . $this->options['css_classes']['item'] . '">' .
                   '<a href="' . htmlspecialchars($url) . '" class="' . $this->options['css_classes']['link'] . '">1</a>' .
                   '</li>' .
                   '<li class="' . $this->options['css_classes']['item'] . ' ' . $this->options['css_classes']['disabled'] . '">' .
                   '<span class="' . $this->options['css_classes']['link'] . '">...</span>' .
                   '</li>';
        }
        
        return '';
    }

    /**
     * Render last page link
     */
    protected function renderLastLink(): string
    {
        $lastPage = $this->paginator->lastPage();
        
        if ($this->paginator->currentPage() < ($lastPage - $this->options['on_each_side'])) {
            $url = $this->paginator->lastPageUrl();
            return '<li class="' . $this->options['css_classes']['item'] . ' ' . $this->options['css_classes']['disabled'] . '">' .
                   '<span class="' . $this->options['css_classes']['link'] . '">...</span>' .
                   '</li>' .
                   '<li class="' . $this->options['css_classes']['item'] . '">' .
                   '<a href="' . htmlspecialchars($url) . '" class="' . $this->options['css_classes']['link'] . '">' . $lastPage . '</a>' .
                   '</li>';
        }
        
        return '';
    }

    /**
     * Render page number links
     */
    protected function renderPageLinks(): string
    {
        $html = '';
        $pageNumbers = $this->paginator->getPageNumbers($this->options['on_each_side']);
        
        foreach ($pageNumbers as $page) {
            if ($page === $this->paginator->currentPage()) {
                $html .= '<li class="' . $this->options['css_classes']['item'] . ' ' . $this->options['css_classes']['active'] . '">' .
                         '<span class="' . $this->options['css_classes']['link'] . '">' . $page . '</span>' .
                         '</li>';
            } else {
                $url = $this->paginator->url($page);
                $html .= '<li class="' . $this->options['css_classes']['item'] . '">' .
                         '<a href="' . htmlspecialchars($url) . '" class="' . $this->options['css_classes']['link'] . '">' . $page . '</a>' .
                         '</li>';
            }
        }
        
        return $html;
    }

    /**
     * Create a PaginatorView instance
     */
    public static function make(Paginator $paginator, array $options = []): self
    {
        return new static($paginator, $options);
    }

    /**
     * Set view type
     */
    public function setView(string $view): self
    {
        $this->options['view'] = $view;
        return $this;
    }

    /**
     * Set CSS classes
     */
    public function setCssClasses(array $classes): self
    {
        $this->options['css_classes'] = array_merge($this->options['css_classes'], $classes);
        return $this;
    }

    /**
     * Magic method to render as string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}