# Laravel DataTables Mongodb Plugin FORK from https://github.com/pimlie/laravel-datatables-mongodb

[![Latest Stable Version](https://img.shields.io/packagist/v/pimlie/laravel-datatables-mongodb.svg)](https://packagist.org/packages/pimlie/laravel-datatables-mongodb)
[![Total Downloads](https://img.shields.io/packagist/dt/pimlie/laravel-datatables-mongodb.svg)](https://packagist.org/packages/pimlie/laravel-datatables-mongodb)
[![License](https://img.shields.io/github/license/pimlie/laravel-datatables-mongodb.svg)](https://packagist.org/packages/pimlie/laravel-datatables-mongodb)

This package is a plugin for [Laravel DataTables](https://github.com/yajra/laravel-datatables) to support Mongodb using [Laravel Mongodb](https://github.com/jenssegers/laravel-mongodb/)

## Requirements
- [Laravel DataTables >=11.0](https://github.com/yajra/laravel-datatables)
- [Laravel Mongodb >= 5.1](https://github.com/jenssegers/laravel-mongodb)

## Documentation
- [Laravel DataTables Documentation](http://yajrabox.com/docs/laravel-datatables)

This plugin provides most functionalities described in the Laravel Datatables documentation. See `Known issues` below

## Installation
`composer require diego-drese/laravel-datatables-mongodb:^1.0`

## Configure

Check the Laravel DataTables configuration for how to configure and use it.

If you want the `datables()` and/or `of` methods to automatically use the correct datatables engine,

Unfortunately we cant use auto-discovery yet, this package will be discoverd before laravel-datatables is and that will overwrite the engines config at the moment


__or__ open the `config/datatables.php` file and add the engines manually to the config:
```php
    /**
     * Datatables list of available engines.
     * This is where you can register your custom datatables engine.
     */
    'engines'        => [
        // The Jenssegers\Mongodb classes extend the default Query/Eloquent classes
        // thus the engines need to be listed above the default engines
        // to make sure they are tried first
        'moloquent'      => DiegoDrese\DataTables\MongodbDataTable::class,
        'mongodb-query'  => DiegoDrese\DataTables\MongodbQueryDataTable::class,
        'mongodb-hybrid' => DiegoDrese\DataTables\HybridMongodbQueryDataTable::class,

        'eloquent'       => Yajra\DataTables\EloquentDataTable::class,
        'query-builder'  => Yajra\DataTables\QueryDataTable::class,
        'collection'     => Yajra\DataTables\CollectionDataTable::class,
    ],

    /**
     * Datatables accepted builder to engine mapping.
     * This is where you can override which engine a builder should use
     * Note, only change this if you know what you are doing!
     */
    'builders'       => [
        MongoDB\Laravel\Eloquent\Builder::class             => 'moloquent',
        MongoDB\Laravel\Query\Builder::class                => 'mongodbQuery',
        MongoDB\Laravel\Helpers\EloquentBuilder::class      => 'eloquent',
        //Illuminate\Database\Eloquent\Relations\Relation::class => 'eloquent',
        //Illuminate\Database\Eloquent\Builder::class            => 'eloquent',
        //Illuminate\Database\Query\Builder::class               => 'query',
        //Illuminate\Support\Collection::class                   => 'collection',
    ],
```

## Usage

### Use the `datatables()` method

For this to work you need to have the class definitions added to the `engines` and `builders` datatables configuration, see above.

```php
use \App\MyMongodbModel;

$datatables = datatables(MyMongodbModel::all());

```

### Use the dataTable class directly.

```php
use Pimlie\DataTables\MongodbDataTable;

return (new MongodbDataTable(App\User::where('id', '>', 1))->toJson()
```

### Use via trait.
- Add the `MongodbDataTableTrait` trait to your model.

```php
use MongoDB\Laravel\Eloquent\Model;
use DiegoDrese\DataTables\Traits\MongodbDataTableTrait;

class User extends Model
{
	use MongodbDataTableTrait;
}
```

- Call dataTable() directly on your model.

```php
Route::get('users/data', function() {
	return User::dataTable()->toJson();
});
```

## Known issues

- the `orderColumn` and `orderColumns` methods are empty placeholders and do nothing
- there is currently no support for viewing/searching/ordering on (non-embedded) relationships between Models (eg through a `user.posts` column key)


