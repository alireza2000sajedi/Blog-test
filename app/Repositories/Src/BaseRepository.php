<?php

namespace App\Repositories\Src;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Exceptions\GeneralException;
use Illuminate\Contracts\Container\BindingResolutionException;
use App\Repositories\Src\Contracts\BaseRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Class BaseRepository.
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * The repository model.
     *
     * @var Model
     */
    protected $model;

    /**
     * The query builder.
     *
     * @var Builder
     */
    protected $query;

    /**
     * The request
     *
     * @var Request
     */
    protected $request;

    /**
     * Alias for the query limit.
     *
     * @var int
     */
    protected $take;

    /**
     * Array of related models to eager load.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Array of one or more where clause parameters.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * Array of one or more where in clause parameters.
     *
     * @var array
     */
    protected $whereIns = [];

    /**
     * Array of one or more ORDER BY column/value pairs.
     *
     * @var array
     */
    protected $orderBys = [];
    /**
     * Has is_active field
     *
     * @var bool
     */
    protected bool $hasIsActive = false;
    /**
     * Value is_active field
     *
     * @var int
     */
    protected int $defaultIsActive = 1;
    /**
     * @var string
     */
    protected string $defaultPrimaryKey = 'id';
    /**
     * @var array
     */
    protected array $filterField = [];

    public string $orderDirection = 'desc';

    public string $orderColumn = 'id';

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
        $this->makeModel();

        $this->request = \request();

        if (config('app.debug'))
            DB::connection()->enableQueryLog();

        if ($this->hasIsActive)
            $this->where('is_active', $this->defaultIsActive);
    }

    /**
     * Specify Model class name.
     *
     * @return mixed
     */
    abstract public function model();

    /**
     * @throws GeneralException|BindingResolutionException
     */
    public function makeModel(): Model
    {
        $model = app()->make($this->model());

        if (!$model instanceof Model) {
            throw new GeneralException("Class {$this->model()} must be an instance of " . Model::class);
        }

        return $this->model = $model;
    }

    /**
     * Get all the model records in the database.
     *
     * @param array $columns
     *
     * @return Collection|static[]
     */
    public function all(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad();

        $models = $this->searchBuilder($this->query)->get($columns);

        $this->unsetClauses();

        return $models;
    }

    /**
     * Count the number of specified model records in the database.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->get()->count();
    }

    /**
     * Create a new model record in the database.
     *
     * @param array $data
     *
     * @return Model
     */
    public function create(array $data): Model
    {
        $this->unsetClauses();

        return $this->model->create($data);
    }

    /**
     * Create one or more new model records in the database.
     *
     * @param array $data
     *
     * @return Collection
     */
    public function createMultiple(array $data): Collection
    {
        $models = new Collection();

        foreach ($data as $d) {
            $models->push($this->create($d));
        }

        return $models;
    }

    /**
     * Delete one or more model records from the database.
     *
     * @return mixed
     */
    public function delete(): mixed
    {
        $this->newQuery()->setClauses();

        $result = $this->query->delete();

        $this->unsetClauses();

        return $result;
    }

    /**
     * Delete the specified model record from the database.
     *
     * @param $id
     *
     * @return bool|null
     * @throws \Exception
     */
    public function deleteById($id): bool
    {
        $this->unsetClauses();

        return $this->find($id)->delete();
    }

    /**
     * Delete multiple records.
     *
     * @param array $ids
     *
     * @return int
     */
    public function deleteMultipleById(array $ids): int
    {
        return $this->model->destroy($ids);
    }

    /**
     * Get the first specified model record from the database.
     *
     * @param array $columns
     *
     * @return Model|static
     */
    public function first(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad()->setClauses();

        $model = $this->query->firstOrFail($columns);

        $this->unsetClauses();

        return $model;
    }

    /**
     * Get all the specified model records in the database.
     *
     * @param array $columns
     *
     * @return Collection|static[]
     */
    public function get(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad()->setClauses();

        $models = $this->query->get($columns);

        $this->unsetClauses();

        return $models;
    }

    /**
     * Get the specified model record from the database.
     *
     * @param       $id
     * @param array $columns
     *
     * @return Collection|Model
     */
    public function find($id, array $columns = ['*'])
    {
        $this->unsetClauses();

        $this->newQuery()->eagerLoad();

        return $this->query->findOrFail($id, $columns);
    }

    /**
     * @param       $item
     * @param       $column
     * @param array $columns
     *
     * @return Model|null|static
     */
    public function getByColumn($item, $column, array $columns = ['*'])
    {
        $this->unsetClauses();

        $this->newQuery()->eagerLoad();

        return $this->query->where($column, $item)->first($columns);
    }

    /**
     * @param int $limit
     * @param array $columns
     * @param string $pageName
     * @param null $page
     *
     * @return LengthAwarePaginator
     */
    public function paginate($limit = 25, array $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginator
    {
        $this->newQuery()->eagerLoad()->setClauses();

        $models = $this->query->paginate($limit, $columns, $pageName, $page);

        $this->unsetClauses();

        return $models;
    }

    /**
     * Update the specified model record in the database.
     *
     * @param       $id
     * @param array $data
     * @param array $options
     *
     * @return Collection|Model
     */
    public function updateById($id, array $data, array $options = [])
    {
        $this->unsetClauses();

        $model = $this->find($id);

        $model->update($data, $options);

        return $model;
    }

    /**
     * Set the query limit.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit(int $limit): BaseRepository
    {
        $this->take = $limit;

        return $this;
    }

    /**
     * Set an ORDER BY clause.
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, $direction = 'asc'): BaseRepository
    {
        $this->orderBys[] = compact('column', 'direction');

        return $this;
    }

    /**
     * Add a simple where clause to the query.
     *
     * @param string $column
     * @param string $value
     * @param string $operator
     *
     * @return $this
     */
    public function where(string $column, string $value, $operator = '='): BaseRepository
    {
        $this->wheres[] = compact('column', 'value', 'operator');

        return $this;
    }

    /**
     * Add a simple where in clause to the query.
     *
     * @param string $column
     * @param mixed $values
     *
     * @return $this
     */
    public function whereIn(string $column, $values): BaseRepository
    {
        $values = is_array($values) ? $values : [$values];

        $this->whereIns[] = compact('column', 'values');

        return $this;
    }

    /**
     * Set Eloquent relationships to eager load.
     *
     * @param $relations
     *
     * @return $this
     */
    public function with($relations): BaseRepository
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        $this->with = $relations;

        return $this;
    }

    public function search()
    {
        $this->newQuery();
        return $this->searchBuilder($this->query)->paginate($this->request->limit ?? 25);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function hasIsActive(bool $value = false): BaseRepository
    {
        $this->hasIsActive = $value;

        return $this;
    }

    /**
     * @param int $value
     * @return BaseRepository
     */
    public function setDefaultIsActive(int $value = 1): BaseRepository
    {
        $this->defaultIsActive = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return BaseRepository
     */
    public function setDefaultPrimaryKey(string $key = 'id'): BaseRepository
    {
        $this->defaultPrimaryKey = $key;

        return $this;
    }

    /**
     * @param array $fields
     * @return BaseRepository
     */
    public function setFilterField(array $fields): BaseRepository
    {
        $this->filterField = $fields;

        return $this;
    }

    /**
     * Create a new instance of the model's query builder.
     *
     * @return $this
     */
    protected function newQuery(): BaseRepository
    {
        $this->query = $this->model->newQuery();

        return $this;
    }

    /**
     * Add relationships to the query builder to eager load.
     *
     * @return $this
     */
    protected function eagerLoad()
    {
        foreach ($this->with as $relation) {
            $this->query->with($relation);
        }

        return $this;
    }

    /**
     * Set clauses on the query builder.
     *
     * @return $this
     */
    protected function setClauses()
    {
        foreach ($this->wheres as $where) {
            $this->query->where($where['column'], $where['operator'], $where['value']);
        }

        foreach ($this->whereIns as $whereIn) {
            $this->query->whereIn($whereIn['column'], $whereIn['values']);
        }

        foreach ($this->orderBys as $orders) {
            $this->query->orderBy($orders['column'], $orders['direction']);
        }

        if (isset($this->take) and !is_null($this->take)) {
            $this->query->take($this->take);
        }

        return $this;
    }

    /**
     * Reset the query clause parameter arrays.
     *
     * @return $this
     */
    protected function unsetClauses()
    {
        $this->wheres = [];
        $this->whereIns = [];
        $this->take = null;

        return $this;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function includeContains($builder)
    {
        if ($this->request->contain) {
            $contains = explode(',', $this->request->contain);
            foreach ($contains as $contain) {
                $camelVersion = Str::camel(trim($contain));
                if (\method_exists($this->model, $camelVersion) || strpos($contain, '.') !== false) {
                    if (strpos($contain, '.') !== false) {
                        $parts = explode('.', $contain);
                        $parts = array_map(function ($part) {
                            return Str::camel($part);
                        }, $parts);
                        $contain = implode(".", $parts);
                    }

                    $builder->with($camelVersion);
                    continue;
                }

                if (\method_exists($this->model, $contain) || strpos($contain, '.') !== false) {
                    $builder->with(trim($contain));
                    continue;
                }
            }
        }

        return $builder;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function includeCounts($builder)
    {
        $count_info = $this->request->count ?? $this->request->with_count ?? null;

        if (!$count_info) {
            return $builder;
        }

        $counters = explode(",", $count_info);

        foreach ($counters as $counter) {
            if (\method_exists($this, $counter)) {
                $builder->withCount($counter);
                continue;
            }

            $camelVersion = Str::camel($counter);
            if (\method_exists($this, $camelVersion)) {
                $builder->withCount($camelVersion);
                continue;
            }
        }

        return $builder;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function applySorts($builder)
    {
        $sorts = $this->request->sort ? explode(',', $this->request->sort) : null;

        if (!$sorts) {
            return $builder->orderBy($this->orderColumn, $this->orderDirection);
        }

        foreach ($sorts as $sort) {
            $sd = explode(":", $sort);
            if ($sd && count($sd) == 2) {
                $builder->orderBy(trim($sd[0]), trim($sd[1]));
            }
        }

        return $builder;
    }

    /**
     * @param $query
     * @return Builder
     */
    protected function searchBuilder($query)
    {
        return $this->buildSearchParams(
            $this->includeContains(
                $this->includeCounts(
                    $this->applySorts($query)
                )));
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function buildSearchParams($builder)
    {
        $operators = [
            '_not'       => '!=',
            '_gt'        => '>',
            '_lt'        => '<',
            '_gte'       => '>=',
            '_lte'       => '<=',
            '_like'      => 'LIKE',
            '_in'        => true,
            '_notIn'     => true,
            '_isNull'    => true,
            '_isNotNull' => true,
        ];
        $searchableFields = array_merge($this->filterField, [
            $this->defaultPrimaryKey,
            $this->model::CREATED_AT,
            $this->model::UPDATED_AT,
        ]);
        foreach ($this->request->all() as $key => $value) {
            if (in_array($key, $searchableFields)) {
                switch ($key) {
                    default:
                        $builder->where($key, '=', $value);
                        break;
                }
            }

            // apply special operators based on the column name passed
            foreach ($operators as $op_key => $op_type) {
                $key = strtolower($key);
                $op_key = strtolower($op_key);

                if (Str::endsWith($key, $op_key) === false) {
                    continue;
                }

                $column_name = Str::replaceLast($op_key, '', $key);

                if (!in_array($column_name, $searchableFields)) {
                    continue;
                }

                if ($op_key == '_in') {
                    $builder->whereIn($column_name, explode(',', $value));
                } else if ($op_key == strtolower('_notIn')) {
                    $builder->whereNotIn($column_name, explode(',', $value));
                } else if ($op_key == strtolower('_isNull')) {
                    $builder->whereNull($column_name);
                } else if ($op_key == strtolower('_isNotNull')) {
                    $builder->whereNotNull($column_name);
                } else if ($op_key == '_like') {
                    $builder->where($column_name, 'LIKE', "%{$value}%");
                } else {
                    $builder->where($column_name, $op_type, $value);
                }
            }
        }

        return $builder;
    }

}
