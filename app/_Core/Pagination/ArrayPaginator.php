<?php
// app/_Core/Pagination/ArrayPaginator.php
namespace Core\Pagination;

class ArrayPaginator implements PaginatorInterface
{
    protected array $data;
    protected $filter;

    public function __construct(array $data, ?callable $filter = null)
    {
        $this->data = $data;
        $this->filter = $filter;
    }

    /**
     * Apply filter to data if provided
     */
    protected function getFilteredData(): array
    {
        if ($this->filter === null) {
            return $this->data;
        }

        return array_filter($this->data, $this->filter);
    }

    /**
     * Get total count of items
     */
    public function count(): int
    {
        return count($this->getFilteredData());
    }

    /**
     * Get items for a specific page
     */
    public function getItems(int $offset, int $limit): array
    {
        $filteredData = $this->getFilteredData();
        return array_slice($filteredData, $offset, $limit, true);
    }

    /**
     * Paginate the array data
     */
    public function paginate(
        int $perPage = 15,
        int $currentPage = 1,
        string $path = '',
        array $queryParams = [],
        string $pageName = 'page'
    ): Paginator {
        $totalItems = $this->count();
        $offset = ($currentPage - 1) * $perPage;
        $items = $this->getItems($offset, $perPage);

        return new Paginator(
            $items,
            $totalItems,
            $perPage,
            $currentPage,
            $path,
            $queryParams,
            $pageName
        );
    }

    /**
     * Create a simple paginated array (static method for convenience)
     */
    public static function make(
        array $data,
        int $perPage = 15,
        int $currentPage = 1,
        string $path = '',
        array $queryParams = [],
        string $pageName = 'page'
    ): Paginator {
        $paginator = new static($data);
        return $paginator->paginate($perPage, $currentPage, $path, $queryParams, $pageName);
    }

    /**
     * Set a filter function for the data
     */
    public function filter(callable $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Sort the data before pagination
     */
    public function sortBy(string $key, string $direction = 'asc'): self
    {
        usort($this->data, function ($a, $b) use ($key, $direction) {
            $aValue = is_array($a) ? ($a[$key] ?? '') : (isset($a->$key) ? $a->$key : '');
            $bValue = is_array($b) ? ($b[$key] ?? '') : (isset($b->$key) ? $b->$key : '');

            if ($direction === 'desc') {
                return $bValue <=> $aValue;
            }
            return $aValue <=> $bValue;
        });

        return $this;
    }

    /**
     * Search within the data
     */
    public function search(string $query, array $searchFields = []): self
    {
        if (empty($query)) {
            return $this;
        }

        $this->filter = function ($item) use ($query, $searchFields) {
            $searchQuery = strtolower($query);

            // If no specific fields provided, search all string values
            if (empty($searchFields)) {
                $values = is_array($item) ? array_values($item) : array_values((array) $item);
                foreach ($values as $value) {
                    if (is_string($value) && str_contains(strtolower($value), $searchQuery)) {
                        return true;
                    }
                }
                return false;
            }

            // Search specific fields
            foreach ($searchFields as $field) {
                $value = is_array($item) ? ($item[$field] ?? '') : (isset($item->$field) ? $item->$field : '');
                if (is_string($value) && str_contains(strtolower($value), $searchQuery)) {
                    return true;
                }
            }

            return false;
        };

        return $this;
    }
}