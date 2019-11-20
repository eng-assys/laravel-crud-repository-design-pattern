<?php

namespace LaravelCrudRepository\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class AbstractRepository
{
    /** @var \Illuminate\Database\Eloquent\Model|null Should contain an Eloquent Model */
    private $model;
    /** @var string|null Should contain a String with Eloquent Model class Name */
    private $modelClass;
    /** @var string|null Should contain a String with Transformer class Name To Use Fractal */
    private $transformerClass;

    public function transformedArray($model)
    {
        return isset($model) ? fractal($model, new $this->transformerClass)->toArray()['data'] : $model;
    }

    /**
     * Load models to Repository
     *
     * @return Model Current Model set to The Repository Class
     *
     */
    public function load($uuid, $modelClass = null, $transformerClass = null)
    {
        $this->modelClass = empty($modelClass) ? $this->modelClass : $modelClass;
        $this->transformerClass = empty($transformerClass) ? $this->transformerClass : $transformerClass;
        $this->model = $this->modelClass::findUuid($uuid);
        return $this;
    }

    public static function validate($functionParams, $validationParams)
    {
        $validator = Validator::make($functionParams, $validationParams);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function find($id)
    {
        return $this->modelClass::find($id);
    }

    public function index(array $criteria=null, array $orderBy = null, $limit = null, $offset = null)
    {
        if(empty($criteria)) {
            return $this->findAll();
        } else {
            return $this->findBy($criteria, $orderBy, $limit, $offset);
        }
    }

    public function getModelAttributes($hiddenAttributes=[])
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
     * List All Models of The Current ModelClass
     *
     * @return array(Model)
     *
     */
    public function findAll()
    {
        return $this->modelClass::all();
    }

    /**
     * Create a new Model
     *
     * @param array $data Required Params to create a new Model
     *
     * @return Model New Model
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
     * @return Model New Model
     *
     */
    public function firstOrCreate(array $data)
    {
        return $this->model = $this->modelClass::firstOrCreate($data);
    }

    /**
     * Show Current Model
     *
     * @return Model Current Model set to The Repository Class
     *
     */
    public function show()
    {
        return $this->model;
    }

    /**
     * Update Current Model
     *
     * @param array $data Required Params to update a Model
     *
     * @return Model Updated Model
     *
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
     *
     */
    public function delete()
    {
        $this->model->delete();
        return $this->model;
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        if (empty($criteria)) return null;

        $trasaction = new $this->modelClass;
        // criteria array to the query
        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $trasaction = $trasaction->where($value[0], $value[1], $value[2]);
            } else {
                $trasaction = $trasaction->where($key, $value);
            }
        }

        // order array to the query
        foreach ($orderBy as $attribute => $order) {
            $trasaction = $trasaction->orderBy($attribute, $order);
        }
        // number of lines to get
        $trasaction = isset($limit) ? $trasaction->take($limit) : $trasaction;
        // start point to the iterator in the database
        $trasaction = isset($offset) ? $trasaction->skip($offset) : $trasaction;

        return $trasaction->get();
    }

    /**
     * Get first Model acconding certain Criteria
     *
     * @param array $criteria The criteria to find some model
     *
     * @return Model Model Model according criteria
     *
     */
    public function findOneBy(array $criteria)
    {
        return $this->findBy($criteria)->first();
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
     * Paginate function to this Model
     *
     * @param $pages Number of pages to paginate
     *
     * @return Collection $collectionPaginated Collection Paginated
     *
     */
    public function paginate($pages)
    {
        return $this->modelClass::paginate($pages);
    }
}
