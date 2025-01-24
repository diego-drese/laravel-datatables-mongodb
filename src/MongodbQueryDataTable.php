<?php

namespace DiegoDrese\DataTables;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\Builder as MoloquentBuilder;
use MongoDB\Laravel\Query\Builder;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Utilities\Helper;

class MongodbQueryDataTable extends QueryDataTable
{
    /**
     * Can the DataTable engine be created with these parameters.
     *
     * @param mixed $source
     * @return boolean
     */
    public static function canCreate($source): bool
    {
        return $source instanceof Builder;
    }

    /**
     * @param \MongoDB\Laravel\Query\Builder $builder
     */
    public function __construct(Builder $builder)
    {
        parent::__construct($builder);
    }

    public function count(): int
    {
        $builder = clone $this->query;

        return $builder->count();
    }

    protected function wrap($column): string
    {
        return $column;
    }

    protected function applyFilterColumn($query, string $columnName, string $keyword, string $boolean = 'and'): void
    {
        $query    = $this->getBaseQueryBuilder($query);
        $callback = $this->columnDef['filter'][$columnName]['method'];

        if ($this->query instanceof MoloquentBuilder) {
            $builder = $this->query->newModelInstance()->newQuery();
        } else {
            $builder = $this->query->newQuery();
        }

        $callback($builder, $keyword);

        $query->addNestedWhereQuery($this->getBaseQueryBuilder($builder), $boolean);
    }

    protected function getBaseQueryBuilder($instance = null): QueryBuilder
    {
        if (!$instance) {
            $instance = $this->query;
        }

        if ($instance instanceof MoloquentBuilder) {
            return $instance->getQuery();
        }

        return $instance;
    }

    protected function compileColumnSearch(int $i, string $column, string $keyword): void
    {
        if ($this->request->isRegex($i)) {
            $this->regexColumnSearch($column, $keyword);
        } else {
            $this->compileQuerySearch($this->query, $column, $keyword, '');
        }
    }

    protected function regexColumnSearch(string $column, string $keyword): void
    {
        $this->query->where($column, 'regex', '/' . $keyword . '/' . ($this->config->isCaseInsensitive() ? 'i' : ''));
    }

    protected function castColumn(string $column): string
    {
        return $column;
    }

    protected function compileQuerySearch($query, string $column, string $keyword, string $boolean = 'or'): void
    {
        $column = $this->castColumn($column);
        $value  = $this->prepareKeyword($keyword);

        if ($this->config->isCaseInsensitive()) {
            $value .= 'i';
        }

        $query->{$boolean . 'Where'}($column, 'regex', $value);
    }

    protected function addTablePrefix($query, $column): string
    {
        return $this->wrap($column);
    }

    protected function prepareKeyword(string $keyword): string
    {
        if ($this->config->isWildcard()) {
            $keyword = Helper::wildcardString($keyword, '.*', $this->config->isCaseInsensitive());
        } elseif ($this->config->isCaseInsensitive()) {
            $keyword = Str::lower($keyword);
        }

        if ($this->config->isSmartSearch()) {
            $keyword = "/.*".$keyword.".*/";
        } else {
            $keyword = "/^".$keyword."$/";
        }

        return $keyword;
    }

    /**
     * Not supported
     * Order each given columns versus the given custom sql.
     *
     * @param array  $columns
     * @param string $sql
     * @param array  $bindings
     * @return $this
     */
    public function orderColumns(array $columns, $sql, $bindings = []): static
    {
        return $this;
    }

    /**
     * Not supported
     * Override default column ordering.
     *
     * @param string $column
     * @param string $sql
     * @param array  $bindings
     * @return $this
     * @internal string $1 Special variable that returns the requested order direction of the column.
     */
    public function orderColumn($column, $sql, $bindings = []): static
    {
        return $this;
    }

    /**
     * Not supported: https://stackoverflow.com/questions/19248806/sort-by-date-with-null-first
     * Set datatables to do ordering with NULLS LAST option.
     *
     * @return $this
     */
    public function orderByNullsLast(): static
    {
        return $this;
    }

    public function paging(): void
    {
        $limit = (int) ($this->request->input('length') > 0 ? $this->request->input('length') : 10);
        if (is_callable($this->limitCallback)) {
            $this->query->limit($limit);
            call_user_func_array($this->limitCallback, [$this->query]);
        } else {
            $start = (int)$this->request->input('start');
            $this->query->skip($start)->take($limit);
        }
    }

    protected function defaultOrdering(): void
    {
        collect($this->request->orderableColumns())
            ->map(function ($orderable) {
                $orderable['name'] = $this->getColumnName($orderable['column'], true);

                return $orderable;
            })
            ->reject(function ($orderable) {
                return $this->isBlacklisted($orderable['name']) && !$this->hasOrderColumn($orderable['name']);
            })
            ->each(function ($orderable) {
                $column = $this->resolveRelationColumn($orderable['name']);

                if ($this->hasOrderColumn($column)) {
                    $this->applyOrderColumn($column, $orderable);
                } else {
                    $this->query->orderBy($column, $orderable['direction']);
                }
            });
    }

    protected function applyOrderColumn(string $column, array $orderable): void
    {
        $this->query->orderBy($column, $orderable['direction']);
    }
}
