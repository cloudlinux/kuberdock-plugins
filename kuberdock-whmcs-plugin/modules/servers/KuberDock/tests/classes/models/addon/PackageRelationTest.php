<?php

namespace tests\models\addon;


use tests\TestCase;
use tests\EloquentMock;
use models\addon\PackageRelation;
use tests\models\billing\PackageStub as Package;

class PackageRelationTest extends TestCase
{
    use EloquentMock;

    public function mockTables()
    {
        return [
            PackageRelation::class,
            Package::class,
        ];
    }

    public function testGet()
    {
        $packageName = 'someTestPackageName';
        $packageRelation = PackageRelation::firstOrNew(['kuber_product_id' => 22]);

        $package = Package::create([
            'id' => 4,
            'gid' => 1,
            'type' => 'other',
            'name' => $packageName,
            'paytype' => 'onetime',
            'autosetup' => 'order',
            'servertype' => KUBERDOCK_MODULE_NAME,
            'servergroup' => 1,
        ]);

        $package->relatedKuberDock()->save($packageRelation);

        $result = PackageRelation::find(4);
        $this->assertEquals(1, $result->count());

        $expected = ['product_id' => 4, 'kuber_product_id' => 22];
        $this->assertEquals($expected, $result->first()->getAttributes());
        $this->assertEquals($packageName, $result->package->name);
    }
}
