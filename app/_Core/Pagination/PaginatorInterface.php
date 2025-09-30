<?php
// app/_Core/Pagination/PaginatorInterface.php
namespace Core\Pagination;

interface PaginatorInterface
{
    /**
     * Paginate the data source
     *
     * @param int $perPage Items per page
     * @param int $currentPage Current page number
     * @param string $path Base URL path
     * @param array $queryParams Additional query parameters
     * @param string $pageName Page parameter name
     * @return Paginator
     */
    public function paginate(
        int $perPage = 15,
        int $currentPage = 1,
        string $path = '',
        array $queryParams = [],
        string $pageName = 'page'
    ): Paginator;

    /**
     * Get total count of items
     *
     * @return int
     */
    public function count(): int;

    /**
     * Get items for a specific page
     *
     * @param int $offset Starting offset
     * @param int $limit Number of items to retrieve
     * @return array
     */
    public function getItems(int $offset, int $limit): array;
}