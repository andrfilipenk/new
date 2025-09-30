<?php
// app/_Core/Pagination/DatabasePaginator.php
namespace Core\Pagination;

use Core\Database\QueryBuilder;
use Core\Database\Model;

class DatabasePaginator implements PaginatorInterface
{
    protected QueryBuilder $query;
    protected ?string $countQuery = null;

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Create from Model class
     */
    public static function fromModel(string $modelClass): self
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException("Class must extend " . Model::class);
        }

        $model = new $modelClass();
        $query = $model->newQuery();
        
        return new static($query);
    }

    /**
     * Add where conditions to the query
     */
    public function where(string $column, $operator = null, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Add order by to the query
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Add join to the query
     */
    public function join(string $table, string $first, string $operator = null, string $second = null): self
    {
        $this->query->join($table, $first, $operator, $second);
        return $this;
    }

    /**
     * Add left join to the query
     */
    public function leftJoin(string $table, string $first, string $operator = null, string $second = null): self
    {
        $this->query->leftJoin($table, $first, $operator, $second);
        return $this;
    }

    /**
     * Set select columns
     */
    public function select(array $columns = ['*']): self
    {
        $this->query->select($columns);
        return $this;
    }

    /**
     * Add search functionality
     */
    public function search(string $query, array $searchFields): self
    {
        if (empty($query) || empty($searchFields)) {
            return $this;
        }

        $this->query->where(function($q) use ($query, $searchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$query}%");
            }
        });

        return $this;
    }

    /**
     * Get total count of items
     */
    public function count(): int
    {
        // Clone the query to avoid modifying the original
        $countQuery = clone $this->query;
        
        // Use custom count query if provided, otherwise use COUNT(*)
        if ($this->countQuery) {
            $result = $countQuery->selectRaw($this->countQuery)->first();
            return (int) array_values((array) $result)[0];
        }

        return $countQuery->count();
    }

    /**
     * Get items for a specific page
     */
    public function getItems(int $offset, int $limit): array
    {
        return $this->query
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Paginate the database query
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
     * Set a custom count query (for complex queries with JOINs)
     */
    public function setCountQuery(string $countQuery): self
    {
        $this->countQuery = $countQuery;
        return $this;
    }

    /**
     * Get the underlying query builder
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * Apply scope to the query
     */
    public function scope(callable $callback): self
    {
        $callback($this->query);
        return $this;
    }

    /**
     * Add where in condition
     */
    public function whereIn(string $column, array $values): self
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    /**
     * Add where not in condition
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->query->whereNotIn($column, $values);
        return $this;
    }

    /**
     * Add where null condition
     */
    public function whereNull(string $column): self
    {
        $this->query->whereNull($column);
        return $this;
    }

    /**
     * Add where not null condition
     */
    public function whereNotNull(string $column): self
    {
        $this->query->whereNotNull($column);
        return $this;
    }

    /**
     * Add where between condition
     */
    public function whereBetween(string $column, array $values): self
    {
        if (count($values) !== 2) {
            throw new \InvalidArgumentException('whereBetween requires exactly 2 values');
        }

        $this->query->where($column, '>=', $values[0])
                   ->where($column, '<=', $values[1]);
        return $this;
    }

    /**
     * Add where date condition
     */
    public function whereDate(string $column, string $operator = null, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->whereRaw("DATE({$column}) {$operator} ?", [$value]);
        return $this;
    }

    /**
     * Group by column
     */
    public function groupBy(string $column): self
    {
        $this->query->groupBy($column);
        return $this;
    }

    /**
     * Add having condition
     */
    public function having(string $column, string $operator = null, $value = null): self
    {
        $this->query->having($column, $operator, $value);
        return $this;
    }
}