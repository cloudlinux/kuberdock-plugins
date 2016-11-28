<?php


namespace models;

use Illuminate\Database\Eloquent\Model as EloquentModel;


class Model extends EloquentModel
{
    public static function createTable()
    {
        $scheme = self::getSchemaBuilder();
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
        $scheme = self::getSchemaBuilder();
        $class = new static;
        $scheme->dropIfExists($class->getTable());
    }

    public static function tableName()
    {
        return (new static)->getTable();
    }

    public static function tableExists()
    {
        $scheme = self::getSchemaBuilder();
        $class = new static;
        return $scheme->hasTable($class->getTable());
    }

    private static function getSchemaBuilder()
    {
        return \models\Model::getConnectionResolver()->connection()->getSchemaBuilder();
    }
}