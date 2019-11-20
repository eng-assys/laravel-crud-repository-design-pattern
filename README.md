# laravel-crud-repository-pattern
An implementation of Repository Pattern to separate CRUD Business Logic using Laravel/Lumen 6.*

* Install in your package the library ```"spatie/laravel-fractal": "^5.6"``` to enable Fractal usage

## Basic Usage

```php

<?php

namespace LaravelCrudRepository\Repositories\AbstractRepository;

use App\Models\Address;

use App\Transformers\AddressTransformer;

class AddressRepository extends AbstractRepository
{
	public function __construct($uuid = null)
	{
        $this->load($uuid, Address::class, AddressTransformer::class);
    }

    public function create($param)
    {
        AddressRepository::validate($param, [
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'complement' => 'required|string|max:255',
            'zip_code' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255'
        ]);

        return parent::create($param);
    }

    public function update($param)
    {
        AddressRepository::validate($param, [
            'street' => 'string|max:255',
            'number' => 'string|max:255',
            'district' => 'string|max:255',
            'complement' => 'string|max:255',
            'zip_code' => 'string|max:255',
            'state' => 'string|max:255',
            'city' => 'string|max:255',
            'country' => 'string|max:255'
        ]);

        return parent::update($param);
    }
}


```