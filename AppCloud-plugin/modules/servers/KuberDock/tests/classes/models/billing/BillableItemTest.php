<?php


namespace classes\models\addon;


use api\Api;
use models\addon\App;
use models\addon\Item;
use models\addon\KubePrice;
use models\addon\KubeTemplate;
use models\addon\PackageRelation;
use models\addon\resource\Pod;
use models\addon\ResourceItems;
use models\addon\ResourcePods;
use models\addon\Resources;
use tests\EloquentMock;
use tests\ExternalApiMock;
use tests\fixtures\ApiFixture;
use tests\fixtures\DatabaseFixture;
use tests\InternalApiMock;
use tests\MakePublicTrait;
use tests\models\billing\AdminStub as Admin;
use tests\models\billing\BillableItemStub as BillableItem;
use tests\models\billing\PackageStub as Package;
use tests\models\billing\ServiceStub as Service;
use tests\models\billing\ClientStub as Client;
use tests\TestCase;

class BillableItemTest extends TestCase
{
    use InternalApiMock;
    use EloquentMock;
    use MakePublicTrait;

    /**
     * Mocked service
     * @var Service
     */
    protected $service;
    /** Mocked
     * @var Api
     */
    protected $api;

    public function setUp()
    {
        parent::setUp();

        Package::insert(DatabaseFixture::package());
        PackageRelation::insert(DatabaseFixture::packageRelation());
        KubeTemplate::insert(DatabaseFixture::kubeTemplates());
        KubePrice::insert(DatabaseFixture::kubePrices());
        Item::create(DatabaseFixture::fixedItem());
        BillableItem::create(DatabaseFixture::billableItem());
        Service::create(DatabaseFixture::service());
        Admin::create(DatabaseFixture::admin());
        App::insert(DatabaseFixture::apps());
        Client::create(DatabaseFixture::client());

        // Mock KD API
        $this->api = (new ExternalApiMock())->externalApiMock();
        // Mock local WHMCS API
        $this->internalApiMock();

        $service = $this->getMockBuilder(Service::class)->setMethods(['getApi', 'getAdminApi'])->getMock();
        $this->service = $service::find(DatabaseFixture::$serviceId);
        $this->service->expects($this->any())->method('getApi')->willReturn($this->api);
        $this->service->expects($this->any())->method('getAdminApi')->willReturn($this->api);
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
            App::class,
            Resources::class,
            ResourcePods::class,
            ResourceItems::class,
            Client::class,
        ];
    }

    public function testRecalculate_NoDividedResource()
    {
        $package = Package::find(DatabaseFixture::$packageIdFixed);
        $pod = new Pod($package);
        $this->service->packageid = $package->id;
        $pod->setService($this->service);
        $pod->setAttributes(ApiFixture::getPodWithResources('Pod id #1'));

        $billableItem = BillableItem::find(DatabaseFixture::$billableItemId);
        $billableItem = $billableItem->recalculate($pod);

        $this->assertEquals(3.2, $billableItem->amount);
    }

    public function testRecalculate_WithDividedResource()
    {
        Item::create(DatabaseFixture::pdItem());
        BillableItem::create(DatabaseFixture::billableItemPD());
        Resources::create(DatabaseFixture::resourcePD());

        $package = Package::find(DatabaseFixture::$packageIdFixed);
        $pod = new Pod($package);
        $this->service->packageid = $package->id;
        $pod->setService($this->service);
        $pod->setAttributes(ApiFixture::getPodWithResources('Pod id #1'));

        $pdBillableItem = BillableItem::find(DatabaseFixture::$pdBillableItemId);
        $this->assertEquals(1, $pdBillableItem->amount);

        $billableItem = BillableItem::find(DatabaseFixture::$billableItemId);
        $billableItem = $billableItem->recalculate($pod);
        $this->assertEquals(1.2, $billableItem->amount);

        $pdBillableItem = $pdBillableItem->fresh();
        $this->assertEquals(2, $pdBillableItem->amount);
    }
}