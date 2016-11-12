<?php

namespace tests;


use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DbTestCase extends TestCase
{
    protected $schema;

    public function setUp()
    {
        parent::setUp();

        $this->configureDatabase();
    }

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
}