<?php
// app/Core/Pagination/Paginator.php
namespace Core\Pagination;

class Paginator
{
    protected int $currentPage;
    protected int $perPage;
    protected int $totalItems;
    protected int $totalPages;
    protected array $items;
    protected string $path;
    protected array $queryParams;
    protected string $pageName;

    public function __construct(
        array $items,
        int $totalItems,
        int $perPage = 15,
        int $currentPage = 1,
        string $path = '',
        array $queryParams = [],
        string $pageName = 'page'
    ) {
        $this->items = $items;
        $this->totalItems = max(0, $totalItems);
        $this->perPage = max(1, $perPage);
        $this->currentPage = max(1, $currentPage);
        $this->path = $path;
        $this->queryParams = $queryParams;
        $this->pageName = $pageName;
        
        $this->calculateTotalPages();
        $this->validateCurrentPage();
    }

    /**
     * Get the items for the current page
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Get current page number
     */
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get items per page
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get total number of items
     */
    public function total(): int
    {
        return $this->totalItems;
    }

    /**
     * Get total number of pages
     */
    public function lastPage(): int
    {
        return $this->totalPages;
    }

    /**
     * Get the first item number on current page
     */
    public function firstItem(): int
    {
        if ($this->totalItems === 0) {
            return 0;
        }
        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    /**
     * Get the last item number on current page
     */
    public function lastItem(): int
    {
        if ($this->totalItems === 0) {
            return 0;
        }
        return min($this->currentPage * $this->perPage, $this->totalItems);
    }

    /**
     * Check if there is a previous page
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Check if there is a next page
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Get previous page number
     */
    public function previousPage(): ?int
    {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }

    /**
     * Get next page number
     */
    public function nextPage(): ?int
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    /**
     * Check if current page is the first page
     */
    public function isFirstPage(): bool
    {
        return $this->currentPage === 1;
    }

    /**
     * Check if current page is the last page
     */
    public function isLastPage(): bool
    {
        return $this->currentPage === $this->totalPages;
    }

    /**
     * Get URL for a specific page
     */
    public function url(int $page): string
    {
        if ($page < 1 || $page > $this->totalPages) {
            return '';
        }

        $params = $this->queryParams;
        $params[$this->pageName] = $page;

        $query = http_build_query($params);
        return $this->path . ($query ? '?' . $query : '');
    }

    /**
     * Get URL for previous page
     */
    public function previousPageUrl(): ?string
    {
        $prev = $this->previousPage();
        return $prev ? $this->url($prev) : null;
    }

    /**
     * Get URL for next page
     */
    public function nextPageUrl(): ?string
    {
        $next = $this->nextPage();
        return $next ? $this->url($next) : null;
    }

    /**
     * Get URL for first page
     */
    public function firstPageUrl(): string
    {
        return $this->url(1);
    }

    /**
     * Get URL for last page
     */
    public function lastPageUrl(): string
    {
        return $this->url($this->totalPages);
    }

    /**
     * Get page numbers for pagination links
     */
    public function getPageNumbers(int $onEachSide = 3): array
    {
        if ($this->totalPages <= ($onEachSide * 2) + 1) {
            return range(1, $this->totalPages);
        }

        $start = max(1, $this->currentPage - $onEachSide);
        $end = min($this->totalPages, $this->currentPage + $onEachSide);

        // Adjust if we're near the beginning or end
        if ($start <= 3) {
            $start = 1;
            $end = min($this->totalPages, ($onEachSide * 2) + 1);
        }

        if ($end >= $this->totalPages - 2) {
            $end = $this->totalPages;
            $start = max(1, $this->totalPages - ($onEachSide * 2));
        }

        return range($start, $end);
    }

    /**
     * Get pagination info as array
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'data' => $this->items,
            'first_page_url' => $this->firstPageUrl(),
            'from' => $this->firstItem(),
            'last_page' => $this->totalPages,
            'last_page_url' => $this->lastPageUrl(),
            'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path,
            'per_page' => $this->perPage,
            'prev_page_url' => $this->previousPageUrl(),
            'to' => $this->lastItem(),
            'total' => $this->totalItems,
        ];
    }

    /**
     * Get pagination info as JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Calculate total pages based on total items and items per page
     */
    protected function calculateTotalPages(): void
    {
        $this->totalPages = (int) ceil($this->totalItems / $this->perPage);
        
        if ($this->totalPages === 0) {
            $this->totalPages = 1;
        }
    }

    /**
     * Validate and adjust current page if necessary
     */
    protected function validateCurrentPage(): void
    {
        if ($this->currentPage > $this->totalPages) {
            $this->currentPage = $this->totalPages;
        }
    }

    /**
     * Set query parameters for URL building
     */
    public function withQueryParams(array $params): self
    {
        $this->queryParams = array_merge($this->queryParams, $params);
        return $this;
    }

    /**
     * Set the base path for URLs
     */
    public function withPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set the page parameter name
     */
    public function withPageName(string $pageName): self
    {
        $this->pageName = $pageName;
        return $this;
    }

    /**
     * Check if there are any items
     */
    public function hasItems(): bool
    {
        return !empty($this->items);
    }

    /**
     * Check if pagination is needed (more than one page)
     */
    public function hasPages(): bool
    {
        return $this->totalPages > 1;
    }

    /**
     * Get range of page numbers for display
     */
    public function getUrlRange(int $start, int $end): array
    {
        $urls = [];
        for ($page = $start; $page <= $end; $page++) {
            $urls[$page] = $this->url($page);
        }
        return $urls;
    }

    /**
     * Magic method to convert to string (returns JSON)
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}