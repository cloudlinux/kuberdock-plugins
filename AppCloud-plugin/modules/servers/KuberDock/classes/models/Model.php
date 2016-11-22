<?php


namespace models;

use Illuminate\Database\Eloquent\Model as EloquentModel;


class Model extends EloquentModel
{
    public static function createTable()
    {
        $scheme = \models\Model::getConnectionResolver()->connection()->getSchemaBuilder();
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

    public static function dropTable()
    {
        $scheme = \models\Model::getConnectionResolver()->connection()->getSchemaBuilder();
        $class = new static;
        $scheme->dropIfExists($class->getTable());
    }

    public static function tableName()
    {
        return (new static)->getTable();
    }
}