<?php


namespace classes\models\addon;


use Carbon\Carbon;
use models\addon\Item;
use models\addon\KubePrice;
use models\addon\KubeTemplate;
use models\addon\PackageRelation;
use models\addon\resource\Pod;
use models\addon\Resources;
use tests\EloquentMock;
use tests\fixtures\ApiFixture;
use tests\fixtures\DatabaseFixture;
use tests\models\billing\AdminStub as Admin;
use tests\models\billing\BillableItemStub as BillableItem;
use tests\models\billing\PackageStub as Package;
use tests\models\billing\ServiceStub as Service;
use tests\TestCase;

class ItemTest extends TestCase
{
    use EloquentMock;

    public function setUp()
    {
        parent::setUp();

        Package::insert(DatabaseFixture::package());
        PackageRelation::insert(DatabaseFixture::packageRelation());
        KubeTemplate::insert(DatabaseFixture::kubeTemplates());
        KubePrice::insert(DatabaseFixture::kubePrices());
        Item::create(DatabaseFixture::fixedItem());
        Service::create(DatabaseFixture::service());
        Admin::create(DatabaseFixture::admin());
    }

    public function mockTables()
    {
        return [
            PackageRelation::class,
            KubeTemplate::class,
            KubePrice::class,
            Item::class,
            BillableItem::class,
            Service::class,
            Package::class,
            Admin::class,
        ];
    }

    public function testRestore_NoBillableItem()
    {
        $service = Service::first();
        $pod = (new Pod($service->package))->setAttributes(ApiFixture::getPodWithResources('pod_id'));

        $stub = $this->getMockBuilder(Item::class)->setMethods(['getPod'])->getMock();
        $stub = $stub->first();
        $stub->status = Resources::STATUS_DELETED;
        $stub->expects($this->once())->method('getPod')->willReturn($pod);
        $stub->restore();

        $expected = [
            'id' => 1,
            'userid' => DatabaseFixture::$userId,
            'description' => 'KuberDock - Pod New Pod #1',
            'recur' => 1,
            'recurcycle' => 'Months',
            'recurfor' => 0,
            'invoiceaction' => 4,
            'amount' => 3.2,
            'invoicecount' => 1,
            'duedate' => (new Carbon())->addDays(10)->format('Y-m-d 00:00:00'),
        ];

        $this->assertEquals($expected, BillableItem::first()->getAttributes());
        $this->assertEquals(Resources::STATUS_ACTIVE, $stub->status);
        $this->assertEquals(1, $stub->billable_item_id);
    }

    public function testRestore_WithBillableItem()
    {
        $service = Service::first();
        $pod = (new Pod($service->package))->setAttributes(ApiFixture::getPodWithResources('pod_id'));

        BillableItem::create([
            'id' => DatabaseFixture::$billableItemId,
            'userid' => DatabaseFixture::$userId,
            'description' => 'KuberDock - Pod New Pod #1',
            'recur' => 1,
            'recurcycle' => 'Months',
            'recurfor' => 0,
            'invoiceaction' => 0,
            'amount' => 0,
            'invoicecount' => 1,
            'duedate' => '2016-12-19 00:00:00',
        ]);

        $stub = $this->getMockBuilder(Item::class)->setMethods(['getPod'])->getMock();
        $stub = $stub->first();
        $stub->status = Resources::STATUS_DELETED;
        $stub->expects($this->once())->method('getPod')->willReturn($pod);
        $stub->restore();

        $expected = [
            'id' => DatabaseFixture::$billableItemId,
            'userid' => DatabaseFixture::$userId,
            'description' => 'KuberDock - Pod New Pod #1',
            'recur' => 1,
            'recurcycle' => 'Months',
            'recurfor' => 0,
            'invoiceaction' => 4,
            'amount' => 3.2,
            'invoicecount' => 1,
            'duedate' => '2016-12-19 00:00:00',
        ];

        $this->assertEquals($expected, BillableItem::first()->getAttributes());
        $this->assertEquals(Resources::STATUS_ACTIVE, $stub->status);
    }
}