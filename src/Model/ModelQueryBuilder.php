<?php

namespace Pecee\Model;

use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Model\Exceptions\ModelNotFoundException;
use Pecee\Pixie\Exception;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;
use Pecee\Pixie\QueryBuilder\QueryObject;
use Pecee\Pixie\QueryBuilder\Raw;
use Pecee\Str;

class ModelQueryBuilder
{
    protected static $instance;

    /**
     * @var Model
     */
    protected $model;
    /**
     * @var QueryBuilderHandler
     */
    protected $query;

    /**
     * ModelQueryBuilder constructor.
     * @param Model $model
     * @throws Exception
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->query = (new QueryBuilderHandler())->table($model->getTable());

        if (app()->getDebugEnabled() === true) {

            $this->query->registerEvent('before-*', $model->getTable(),
                function (QueryBuilderHandler $qb, QueryObject $qo) {
                    debug('START QUERY: ' . $qo->getRawSql());
                });

            $this->query->registerEvent('after-*', $model->getTable(),
                function (QueryBuilderHandler $qb, QueryObject $qo) {
                    debug('END QUERY: ' . $qo->getRawSql());
                });
        }
    }

    /**
     * @param \stdClass $item
     * @return Model
     */
    protected function createInstance(\stdClass $item)
    {
        /* @var $model Model */
        $model = get_class($this->model);
        $model = new $model();
        $model->with($this->model->getWith());
        $model->mergeRows((array)$item);
        $model->setOriginalRows((array)$item);
        $model->hideFields($this->model->getHiddenFields());
        $model->onInstanceCreate();

        return $model;
    }

    protected function createCollection(array $items)
    {
        $collection = $this->model->onCollectionCreate($items);
        $collection->setType(static::class);

        return $collection;
    }

    /**
     * @param string $alias
     * @return static
     */
    public function alias($alias)
    {
        $this->query->alias($alias, $this->model->getTable());

        return $this->model;
    }

    /**
     * @param int $limit
     * @return static
     */
    public function limit($limit)
    {
        $this->query->limit($limit);

        return $this->model;
    }

    /**
     * @param int $skip
     * @return static
     */
    public function skip($skip)
    {
        $this->query->offset($skip);

        return $this->model;
    }

    /**
     * @param int $amount
     * @return static
     */
    public function take($amount)
    {
        return $this->limit($amount);
    }

    /**
     * @param int $offset
     * @return static
     */
    public function offset($offset)
    {
        return $this->skip($offset);
    }

    /**
     * @param string|Raw|\Closure $key
     * @param string|null $operator
     * @param mixed|Raw|\Closure|null $value
     * @return static
     */
    public function where($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->where($key, $operator, $value);

        return $this->model;
    }

    /**
     * @param string|Raw|\Closure $key
     * @param array|Raw|\Closure $values
     *
     * @return static
     */
    public function whereIn($key, $values)
    {
        $this->query->whereIn($key, $values);

        return $this->model;
    }

    /**
     * Adds WHERE NOT statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param string|array|Raw|\Closure|null $operator
     * @param mixed|Raw|\Closure|null $value
     *
     * @return static
     */
    public function whereNot($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->whereNot($key, $operator, $value);

        return $this->model;
    }

    /**
     * Adds OR WHERE NOT IN statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param array|Raw|\Closure $values
     *
     * @return static
     */
    public function whereNotIn($key, $values)
    {
        $this->query->whereNotIn($key, $values);

        return $this->model;
    }

    /**
     * Adds WHERE NULL statement to the current query.
     *
     * @param string|Raw|\Closure $key
     *
     * @return static
     */
    public function whereNull($key)
    {
        $this->query->whereNull($key);

        return $this->model;
    }

    /**
     * Adds WHERE NOT NULL statement to the current query.
     *
     * @param string|Raw|\Closure $key
     *
     * @return static
     */
    public function whereNotNull($key)
    {
        $this->query->whereNotNull($key);

        return $this->model;
    }

    /**
     * Adds WHERE BETWEEN statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param string|integer|float $valueFrom
     * @param string|integer|float $valueTo
     *
     * @return static
     */
    public function whereBetween($key, $valueFrom, $valueTo)
    {
        $this->query->whereBetween($key, $valueFrom, $valueTo);

        return $this->model;
    }

    /**
     * Adds OR WHERE statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param string|null $operator
     * @param mixed|Raw|\Closure|null $value
     *
     * @return static
     */
    public function orWhere($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->orWhere($key, $operator, $value);

        return $this->model;
    }

    /**
     * Adds OR WHERE IN statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param array|Raw|\Closure $values
     *
     * @return static
     */
    public function orWhereIn($key, $values)
    {
        $this->query->orWhereIn($key, $values);

        return $this->model;
    }

    /**
     * Adds or WHERE NOT IN statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param array|Raw|\Closure $values
     *
     * @return static
     */
    public function orWhereNotIn($key, $values)
    {
        $this->query->orWhereNotIn($key, $values);

        return $this->model;
    }

    /**
     * Adds OR WHERE NOT statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param string|null $operator
     * @param mixed|Raw|\Closure|null $value
     *
     * @return static
     */
    public function orWhereNot($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->orWhereNot($key, $operator, $value);

        return $this->model;
    }

    /**
     * Adds OR WHERE NULL statement to the current query.
     *
     * @param string|Raw|\Closure $key
     *
     * @return static
     */
    public function orWhereNull($key)
    {
        $this->query->orWhereNull($key);

        return $this->model;
    }

    /**
     * Adds OR WHERE NOT NULL statement to the current query.
     *
     * @param string|Raw|\Closure $key
     *
     * @return static
     */
    public function orWhereNotNull($key)
    {
        $this->query->orWhereNotNull($key);

        return $this->model;
    }

    /**
     * Adds OR WHERE BETWEEN statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param string|integer|float $valueFrom
     * @param string|integer|float $valueTo
     *
     * @return static
     */
    public function orWhereBetween($key, $valueFrom, $valueTo)
    {
        $this->query->orWhereBetween($key, $valueFrom, $valueTo);

        return $this->model;
    }

    /**
     * @return ModelCollection
     * @throws Exception
     */
    public function get()
    {
        return $this->all();
    }

    /**
     * @return ModelCollection
     * @throws Exception
     */
    public function all()
    {
        $items = $this->query->get();

        /* @var $model Model */
        $models = [];

        foreach ($items as $item) {
            $models[] = $this->createInstance($item);
        }

        return $this->createCollection($models);
    }

    /**
     * @return static
     * @throws Exception
     */
    public function find($id)
    {
        $item = $this->query->where($this->model->getPrimary(), '=', $id)->first();
        if ($item !== null) {
            return $this->createInstance($item);
        }

        return null;
    }

    /**
     * @return static
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function findOrFail($id)
    {
        $item = $this->find($id);
        if ($item === null) {
            throw new ModelNotFoundException(ucfirst(Str::camelize($this->model->getTable())) . ' was not found');
        }

        return $item;
    }

    /**
     * @return static|null
     * @throws Exception
     */
    public function first()
    {
        $item = $this->query->first();
        if ($item !== null) {
            return $this->createInstance($item);
        }

        return null;
    }

    /**
     * @return static
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function firstOrFail()
    {
        $item = $this->first();
        if ($item === null) {
            throw new ModelNotFoundException(ucfirst(Str::camelize($this->model->getTable())) . ' was not found');
        }

        return $item;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function count()
    {
        return $this->query->count();
    }

    /**
     * return int
     * @throws Exception
     */
    public function max($field)
    {
        $result = $this->query->select($this->query->raw('MAX(' . $field . ') AS max'))->get();

        return (int)$result[0]->max;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function sum($field)
    {
        $result = $this->query->select($this->query->raw('SUM(' . $field . ') AS sum'))->get();

        return (int)$result[0]->sum;
    }

    /**
     * Get valid data
     * @param array $data
     * @return array
     */
    protected function getValidData(array $data)
    {
        $out = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $this->model->getColumns(), true) === true) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @param array $data
     * @return static
     * @throws Exception
     * @throws ModelException
     */
    public function update(array $data = [])
    {
        if (count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $this->query->update($data);

        return $this->model;
    }

    /**
     * @param array $data
     * @return static|null
     * @throws Exception
     * @throws ModelException
     */
    public function create(array $data = [])
    {
        $data = array_merge($this->model->getRows(), $this->getValidData($data));

        if (count($data) === 0) {
            throw new ModelException('Not valid columns found to update.');
        }

        $id = $this->query->insert($data);

        if ($id !== null) {

            $this->model->mergeRows($data);
            $this->model->{$this->model->getPrimary()} = $id;

            return $this->model;
        }

        return null;
    }

    /**
     * @param array $data
     * @return static
     * @throws Exception
     * @throws ModelException
     */
    public function firstOrCreate(array $data = [])
    {
        $item = $this->first();

        if ($item === null) {
            $item = $this->createInstance((object)$data);
            $item->setOriginalRows([]);
            $item->save();
        }

        return $item;
    }

    /**
     * @param array $data
     * @return static
     * @throws Exception
     */
    public function firstOrNew(array $data = [])
    {
        $item = $this->first();

        if ($item === null) {
            $item = $this->createInstance((object)$data);
            $item->setOriginalRows([]);
        }

        return $item;
    }

    /**
     * @param array $ids
     * @return static
     * @throws Exception
     */
    public function destroy(array $ids)
    {
        $this->query->whereIn('id', $ids)->delete();

        return $this->model;
    }

    /**
     * @param string|array $fields
     * @return static
     */
    public function select($fields)
    {
        $this->query->select($fields);

        return $this->model;
    }

    /**
     * @param string|Raw|\Closure|array $field
     * @return static
     */
    public function groupBy($field)
    {
        $this->query->groupBy($field);

        return $this->model;
    }

    /**
     * Adds ORDER BY statement to the current query.
     *
     * @param string|Raw|\Closure|array $fields
     * @param string $defaultDirection
     *
     * @return static
     */
    public function orderBy($fields, $defaultDirection = 'ASC')
    {
        $this->query->orderBy($fields, $defaultDirection);

        return $this->model;
    }

    /**
     * Adds new JOIN statement to the current query.
     *
     * @param string|Raw|\Closure|array $table
     * @param string|Raw|\Closure $key
     * @param string|null $operator
     * @param string|Raw|\Closure $value
     * @param string $type
     *
     * @return static
     * @throws Exception
     *
     * ```
     * Examples:
     * - basic usage
     * ->join('table2', 'table2.person_id', '=', 'table1.id');
     *
     * - as alias 'bar'
     * ->join(['table2','bar'], 'bar.person_id', '=', 'table1.id');
     *
     * - complex usage
     * ->join('another_table', function($table)
     * {
     *  $table->on('another_table.person_id', '=', 'my_table.id');
     *  $table->on('another_table.person_id2', '=', 'my_table.id2');
     *  $table->orOn('another_table.age', '>', $queryBuilder->raw(1));
     * })
     * ```
     */
    public function join($table, $key, $operator = null, $value = null, $type = 'inner')
    {
        $this->query->join($table, $key, $operator, $value, $type);

        return $this->model;
    }

    /**
     * Adds a raw string to the current query.
     * This query will be ignored from any parsing or formatting by the Query builder
     * and should be used in conjunction with other statements in the query.
     *
     * For example: $qb->where('result', '>', $qb->raw('COUNT(`score`)));
     *
     * @param string $value
     * @param array|null|mixed $bindings ...
     *
     * @return Raw
     */
    public function raw($value, array $bindings = [])
    {
        return $this->query->raw($value, $bindings);
    }

    /**
     * @param Model $model
     * @param null $alias
     * @return Raw
     * @throws Exception
     */
    public function subQuery(Model $model, $alias = null)
    {
        return $this->query->subQuery($model->getQuery(), $alias);
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param Model $model
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @return QueryBuilderHandler
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * Get unique identifier for current query
     * @return string
     * @throws Exception
     */
    public function getQueryIdentifier()
    {
        return md5(static::class . $this->getQuery()->getQuery()->getRawSql());
    }

    public function __sleep()
    {
        return ['model'];
    }

    /**
     * @throws Exception
     */
    public function __wakeup()
    {
        $this->query = (new QueryBuilderHandler())->table($this->model->getTable());
    }

}