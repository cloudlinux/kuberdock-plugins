<?php


namespace models;


use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    public static function createTable()
    {
        $class = new static;
        self::DB()->getSchemaBuilder()->create(self::tableName(), $class->getSchema());
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
        self::DB()->getSchemaBuilder()->dropIfExists(self::tableName());
    }

    public static function tableName()
    {
        return (new static)->getTable();
    }

    public static function tableExists()
    {
        return self::DB()->getSchemaBuilder()->hasTable(self::tableName());
    }

    public static function truncateTable()
    {
        self::DB()->table(self::tableName())->truncate();
    }

    private static function DB()
    {
        return \models\Model::getConnectionResolver()->connection();
    }
}