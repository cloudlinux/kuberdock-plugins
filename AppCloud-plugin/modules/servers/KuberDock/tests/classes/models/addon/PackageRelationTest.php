<?php

namespace tests\models\addon;


use tests\DbTestCase;
use models\addon\PackageRelation;
use models\billing\Package;

class PackageRelationTest extends DbTestCase
{
    private $packageName = 'someTestPackageName';

    public function setUp()
    {
        parent::setUp();

        PackageRelation::createTable($this->schema);

        $this->schema->create('tblproducts', function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('name');
            $table->timestamp('updated_at');
            $table->timestamp('created_at');
        });
    }

    public function tearDown()
    {
        $this->schema->dropIfExists('tblproducts');
        $this->schema->dropIfExists('KuberDock_products');

        parent::tearDown();
    }

    public function testGet()
    {
        $packageRelation = PackageRelation::firstOrNew(['kuber_product_id' => 22]);
        $package = Package::create(['id' => 4, 'name' => $this->packageName]);
        $package->relatedKuberDock()->save($packageRelation);

        $result = PackageRelation::find(4);
        $this->assertEquals(1, $result->count());

        $expected = ['product_id' => 4, 'kuber_product_id' => 22];
        $this->assertEquals($expected, $result->first()->getAttributes());
        $this->assertEquals($this->packageName, $result->package->name);
    }
}
