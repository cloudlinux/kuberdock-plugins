<?php

namespace tests;


use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model as Eloquent;
use models\Model;

trait EloquentMock
{
    protected $schema;

    protected function configureDatabase()
    {
        $db = new Capsule;
        $db->addConnection(array(
            'driver'    => 'sqlite',
            'database'  => ':memory:',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ));
        $db->bootEloquent();
        $db->setAsGlobal();

        Eloquent::unguard();

        $this->schema = Capsule::schema();
    }

    abstract public function mockTables();

    public function createTables()
    {
        foreach ($this->mockTables() as $table) {
            /** @var $table Model */
            $table::createTable();
        }
    }

    public function dropTables()
    {
        foreach (array_reverse($this->mockTables()) as $table) {
            /** @var $table Model */
            $table::dropTable();
        }
    }
}