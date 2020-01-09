<?php

namespace LaravelCrudRepository\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class AbstractRepository
{
    /** 
     * @var \Illuminate\Database\Eloquent\Model|null Should contain an Eloquent Model
     */
    private $model;

    /**
     *  @var string|null Should contain a String with Eloquent Model class Name 
     */
    private $modelClass;

    /**
     * @var string|null Should contain a String with Transformer class Name To Use Fractal
     */
    private $transformerClass;

    public function transformedArray($model)
    {
        return isset($model) ? fractal($model, new $this->transformerClass)->toArray()['data'] : $model;
    }

    public function transformedPagination($model)
    {
        return isset($model) ? fractal($model, new $this->transformerClass) : $model;
    }

    /**
     * Find Model by it uuid and Load to Repository
     *
     * @return Model Current Model set to The Repository Class
     */
    public function load($uuid, $modelClass = null, $transformerClass = null)
    {
        $this->modelClass = empty($modelClass) ? $this->modelClass : $modelClass;
        $this->transformerClass = empty($transformerClass) ? $this->transformerClass : $transformerClass;
        $this->model = $this->modelClass::findUuid($uuid);
        return $this;
    }

    /**
     * @param Model $model Model to be loaded to repository
     * 
     * @return Model $model Current Model set to The Repository Class
     */
    public function loadModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public static function validate($functionParams, $validationParams)
    {
        $validator = Validator::make($functionParams, $validationParams);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Find a Model by its ID and load it to $model attribute
     *
     * @param integer $id Model Id
     *
     * @return mixed $model Loaded Model
     */
    public function findAndLoad($id)
    {
        $model = $this->find($id);
        $this->model = $model;
        return $model;
    }

    /**
     * List all model results based on parameters
     *
     * @param array $criteria criteria array used to filter results
     * @param array $orderBy properties array used to sort results
     * @param integer $limit To limit the number of results returned from the query
     * @param integer $offset To skip a given number of results in the query
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(array $criteria = null, array $orderBy = [], $limit = null, $offset = null)
    {
        if (empty($criteria)) {
            return $this->findAll();
        } else {
            return $this->findBy($criteria, $orderBy, $limit, $offset)->get();
        }
    }

    /**
     * Paginate model based on criteria
     *
     * @param array $criteria criteria array used to filter results
     * @param integer $itemPerPage number of items per page
     * 
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate($criteria = [], $itemsPerPage = 10, $customQuery = null)
    {
        return $this->findBy($criteria ?? [], [], null, null, $customQuery)->paginate($itemsPerPage ?? 10);
    }

    /**
     * To retrieve a single row by your primary key column value
     * @param mixed $id
     * 
     * @return Model
     */
    public function find($id)
    {
        return $this->modelClass::find($id);
    }

    /**
     * List All Models of The Current ModelClass
     *
     * @return array(Model)
     */
    public function findAll()
    {
        return $this->modelClass::all();
    }

    /**
     * Get first Model acconding certain Criteria
     *
     * @param array $criteria The criteria to find some model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findOneBy(array $criteria)
    {
        return $this->findBy($criteria)->first();
    }

    /**
     * List all model results based on parameters
     *
     * @param array $criteria criteria array used to filter results
     * @param array $orderBy properties array used to sort results
     * @param integer $limit To limit the number of results returned from the query
     * @param integer $offset To skip a given number of results in the query
     * @param \Illuminate\Database\Eloquent\Builder $customQuery used to initialize the transaction
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function findBy(array $criteria, array $orderBy = [], $limit = null, $offset = null, $customQuery = null)
    {
        $transaction = $customQuery ?? new $this->modelClass;

        // criteria array to the query
        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $transaction = $transaction->where($value[0], $value[1], $value[2]);
            } else {
                $transaction = $transaction->where($key, $value);
            }
        }

        // order array to the query
        foreach ($orderBy as $attribute => $order) {
            $transaction = $transaction->orderBy($attribute, $order);
        }

        // number of lines to get
        $transaction = isset($limit) ? $transaction->take($limit) : $transaction;

        // start point to the iterator in the database
        $transaction = isset($offset) ? $transaction->skip($offset) : $transaction;

        return $transaction;
    }

    public function getModelAttributes($hiddenAttributes = [])
    {
        $modelAttributes = isset($this->model) ? array_keys($this->model->getAttributes()) : [];

        foreach ($hiddenAttributes as $hidden) {
            if (($key = array_search($hidden, $modelAttributes)) !== false) {
                unset($modelAttributes[$key]);
            }
        }

        return $modelAttributes;
    }

    /**
     * Create a new Model
     *
     * @param array $data Required Params to create a new Model
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     */
    public function create(array $data)
    {
        return $this->model = $this->modelClass::create($data);
    }

    /**
     * Create a new Model only if it doesn't exist yet
     *
     * @param array $data Required Params to create a new Model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $data)
    {
        return $this->model = $this->modelClass::firstOrCreate($data);
    }

    /**
     * Show Current Model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function show()
    {
        return $this->model->refresh();
    }

    /**
     * Update Current Model
     *
     * @param array $data Required Params to update a Model
     *
     * @return \Illuminate\Database\Eloquent\Model Updated Model
     */
    public function update(array $data)
    {
        $this->model->update($data);
        return $this->model;
    }

    /**
     * Destroy Current Model
     *
     * @return Model Model before elimination
     */
    public function delete()
    {
        $this->model->delete();
        return $this->model;
    }

    // from Doctrine
    public function __call($method, $arguments)
    {
        if (!isset($arguments[0])) {
            throw new \Exception('You must have one argument');
        }

        if (substr($method, 0, 6) == 'findBy') {
            $by = substr($method, 6, strlen($method));
            $method = 'findBy';
        } else if (substr($method, 0, 9) == 'findOneBy') {
            $by = substr($method, 9, strlen($method));
            $method = 'findOneBy';
        } else {
            throw new \Exception(
                "Undefined method '$method'. The method name must start with " .
                    "either findBy or findOneBy!"
            );
        }

        return $this->$method([[lcfirst($by), '=', $arguments[0]]]);
    }

    /**
     * Update a model if it does not exist; otherwise create it
     *
     * @param array $modelArray Parameters to create or update a model
     * @param array $queryKeys Keys from modelArray to use as search criteria
     *
     * @return \Illuminate\Database\Eloquent\Model Created Model
     *
     */    
    public function updateOrCreate(array $modelArray, array $queryKeys)
    {
        $criteria = [];

        foreach ($queryKeys as $queryKey) {
            $criteria[$queryKey] = $modelArray[$queryKey];
        }

        $existentModel = $this->findOneBy($criteria);

        if (isset($existentModel)) {
            return $this->loadModel($existentModel)->update($modelArray);
        } else {
            return $this->create($modelArray);
        }
    }
}
