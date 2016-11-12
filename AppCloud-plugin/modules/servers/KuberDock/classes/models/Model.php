<?php


namespace models;

use Illuminate\Database\Eloquent\Model as EloquentModel;


class Model extends EloquentModel
{
    /**
     * @param \Illuminate\Database\Schema\Builder $scheme
     */
    public static function createTable($scheme)
    {
        $class = new static;
        $scheme->create($class->getTable(), $class->getSchema());
    }

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {};
    }
}